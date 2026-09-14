<?php

require_once __DIR__ . '/../auth/session.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit;
}

$prometheusUrl = 'http://127.0.0.1:9090/api/v1/query';

function queryPrometheus($query)
{
    global $prometheusUrl;

    $ch = curl_init($prometheusUrl . '?query=' . urlencode($query));

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 1.2,
        CURLOPT_CONNECTTIMEOUT => 0.5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('Gagal menghubungi Prometheus: ' . $error);
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('Prometheus HTTP error: ' . $httpCode);
    }

    $data = json_decode($response, true);

    if (!is_array($data) || ($data['status'] ?? '') !== 'success') {
        throw new Exception($data['error'] ?? 'Query Prometheus gagal.');
    }

    return $data;
}

function prometheusSafe($query)
{
    try {
        return queryPrometheus($query);
    } catch (Exception $e) {
        return null;
    }
}

function getPrometheusValue($data, $default = 0)
{
    if (
        !isset($data['data']['result'][0]['value'][1])
    ) {
        return $default;
    }

    return (float) $data['data']['result'][0]['value'][1];
}

function getResourceStatus($value)
{
    if ($value >= 90) {
        return 'critical';
    }

    if ($value >= 75) {
        return 'warning';
    }

    return 'healthy';
}

function clampPercent($value)
{
    return max(0, min(100, (float) $value));
}

function bytesToGb($bytes)
{
    return round(((float) $bytes) / 1024 / 1024 / 1024, 2);
}

function detectOsFamily()
{
    $family = PHP_OS_FAMILY;
    $uname = strtolower(php_uname('s'));

    if ($family === 'Windows' || str_contains($uname, 'win')) {
        return 'windows';
    }

    if ($family === 'Darwin' || str_contains($uname, 'darwin')) {
        return 'macos';
    }

    return 'linux';
}

function localHostname()
{
    $name = gethostname();

    if (is_string($name) && $name !== '') {
        return $name;
    }

    return php_uname('n') ?: 'localhost';
}

function localIpAddress()
{
    $candidates = [];

    if (!empty($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] !== '::1') {
        $candidates[] = $_SERVER['SERVER_ADDR'];
    }

    $resolved = gethostbyname(localHostname());
    if (filter_var($resolved, FILTER_VALIDATE_IP) && $resolved !== '127.0.0.1') {
        $candidates[] = $resolved;
    }

    if (function_exists('socket_create')) {
        $sock = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($sock) {
            @socket_connect($sock, '8.8.8.8', 53);
            if (@socket_getsockname($sock, $addr)) {
                $candidates[] = $addr;
            }
            @socket_close($sock);
        }
    }

    foreach ($candidates as $ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $ip;
        }
    }

    foreach ($candidates as $ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP) && $ip !== '127.0.0.1' && $ip !== '::1') {
            return $ip;
        }
    }

    return '127.0.0.1';
}

function diskRoot()
{
    return detectOsFamily() === 'windows' ? 'C:\\' : '/';
}

function collectDisk()
{
    $root = diskRoot();
    $total = @disk_total_space($root);
    $free = @disk_free_space($root);

    if (!$total) {
        return [
            'usage' => 0,
            'total_gb' => 0,
            'used_gb' => 0,
            'free_gb' => 0,
            'status' => 'healthy',
        ];
    }

    $used = $total - (float) $free;
    $usage = clampPercent(($used / $total) * 100);

    return [
        'usage' => round($usage, 2),
        'total_gb' => bytesToGb($total),
        'used_gb' => bytesToGb($used),
        'free_gb' => bytesToGb($free),
        'status' => getResourceStatus($usage),
        'mount' => $root,
    ];
}

function runPowershell($script)
{
    $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ermonitor-host.ps1';
    file_put_contents($file, $script);

    $cmd = 'powershell -NoProfile -NonInteractive -ExecutionPolicy Bypass -File '
        . escapeshellarg($file);

    $output = [];
    $code = 1;
    @exec($cmd, $output, $code);

    if ($code !== 0 || !$output) {
        return null;
    }

    $json = json_decode(implode("\n", $output), true);

    return is_array($json) ? $json : null;
}

function collectWindowsLocal()
{
    $ps = <<<'PS'
$ErrorActionPreference = 'SilentlyContinue'
$cpu = 0
$proc = Get-CimInstance Win32_PerfFormattedData_PerfOS_Processor -Filter "Name='_Total'"
if ($proc) { $cpu = [double]$proc.PercentProcessorTime }
$os = Get-CimInstance Win32_OperatingSystem
@{
  cpu = $cpu
  total = [int64]$os.TotalVisibleMemorySize * 1024
  free = [int64]$os.FreePhysicalMemory * 1024
} | ConvertTo-Json -Compress
PS;

    $data = runPowershell($ps);

    if (!$data && class_exists('COM')) {
        try {
            $wmi = new COM('WinMgmts:\\\\.\\root\\cimv2');
            $cpuSum = 0;
            $cpuCount = 0;
            foreach ($wmi->ExecQuery('SELECT LoadPercentage FROM Win32_Processor') as $cpu) {
                $cpuSum += (float) $cpu->LoadPercentage;
                $cpuCount++;
            }
            $os = null;
            foreach ($wmi->ExecQuery('SELECT TotalVisibleMemorySize,FreePhysicalMemory FROM Win32_OperatingSystem') as $row) {
                $os = $row;
                break;
            }
            if ($os) {
                $data = [
                    'cpu' => $cpuCount ? $cpuSum / $cpuCount : 0,
                    'total' => (float) $os->TotalVisibleMemorySize * 1024,
                    'free' => (float) $os->FreePhysicalMemory * 1024,
                ];
            }
        } catch (Exception $e) {
            $data = null;
        }
    }

    if (!$data) {
        return null;
    }

    $total = (float) ($data['total'] ?? 0);
    $free = (float) ($data['free'] ?? 0);
    $used = max(0, $total - $free);
    $ram = $total > 0 ? ($used / $total) * 100 : 0;

    return [
        'cpu' => clampPercent($data['cpu'] ?? 0),
        'ram' => clampPercent($ram),
        'memory_total' => $total,
        'memory_used' => $used,
        'memory_free' => $free,
    ];
}

function readProcStat()
{
    $line = @file('/proc/stat', FILE_IGNORE_NEW_LINES);
    if (!$line) {
        return null;
    }

    foreach ($line as $row) {
        if (str_starts_with($row, 'cpu ')) {
            $parts = preg_split('/\s+/', trim($row));
            $nums = array_map('floatval', array_slice($parts, 1));
            $idle = ($nums[3] ?? 0) + ($nums[4] ?? 0);
            $total = array_sum($nums);
            return ['idle' => $idle, 'total' => $total];
        }
    }

    return null;
}

function collectLinuxLocal()
{
    $sampleA = readProcStat();
    usleep(180000);
    $sampleB = readProcStat();

    $cpu = 0;
    if ($sampleA && $sampleB) {
        $dTotal = $sampleB['total'] - $sampleA['total'];
        $dIdle = $sampleB['idle'] - $sampleA['idle'];
        if ($dTotal > 0) {
            $cpu = (1 - ($dIdle / $dTotal)) * 100;
        }
    }

    $meminfo = @file('/proc/meminfo', FILE_IGNORE_NEW_LINES) ?: [];
    $map = [];
    foreach ($meminfo as $row) {
        if (preg_match('/^(\w+):\s+(\d+)/', $row, $m)) {
            $map[$m[1]] = ((float) $m[2]) * 1024;
        }
    }

    $total = $map['MemTotal'] ?? 0;
    $available = $map['MemAvailable'] ?? (($map['MemFree'] ?? 0) + ($map['Buffers'] ?? 0) + ($map['Cached'] ?? 0));
    $used = max(0, $total - $available);
    $ram = $total > 0 ? ($used / $total) * 100 : 0;

    return [
        'cpu' => clampPercent($cpu),
        'ram' => clampPercent($ram),
        'memory_total' => $total,
        'memory_used' => $used,
        'memory_free' => $available,
    ];
}

function collectMacLocal()
{
    $total = (float) trim((string) @shell_exec('sysctl -n hw.memsize'));
    $pageSize = (float) trim((string) @shell_exec('pagesize'));
    if ($pageSize <= 0) {
        $pageSize = 4096;
    }

    $vm = (string) @shell_exec('vm_stat');
    $freePages = 0;
    if (preg_match('/Pages free:\s+(\d+)/', $vm, $m)) {
        $freePages += (float) $m[1];
    }
    if (preg_match('/Pages speculative:\s+(\d+)/', $vm, $m)) {
        $freePages += (float) $m[1];
    }

    $free = $freePages * $pageSize;
    $used = max(0, $total - $free);
    $ram = $total > 0 ? ($used / $total) * 100 : 0;

    $load = function_exists('sys_getloadavg') ? sys_getloadavg() : [0];
    $ncpu = (float) trim((string) @shell_exec('sysctl -n hw.ncpu')) ?: 1;
    $cpu = clampPercent((($load[0] ?? 0) / $ncpu) * 100);

    return [
        'cpu' => $cpu,
        'ram' => clampPercent($ram),
        'memory_total' => $total,
        'memory_used' => $used,
        'memory_free' => $free,
    ];
}

function collectLocalMetrics()
{
    $os = detectOsFamily();
    $metrics = null;

    if ($os === 'windows') {
        $metrics = collectWindowsLocal();
    } elseif ($os === 'macos') {
        $metrics = collectMacLocal();
    } else {
        $metrics = collectLinuxLocal();
    }

    if (!$metrics) {
        $metrics = [
            'cpu' => 0,
            'ram' => 0,
            'memory_total' => 0,
            'memory_used' => 0,
            'memory_free' => 0,
        ];
    }

    $metrics['os'] = $os;
    $metrics['source'] = 'local';

    return $metrics;
}

function readWindowsExporterSample($text)
{
    $idle = 0.0; $total = 0.0; $memTotal = 0.0; $memFree = 0.0;
    foreach (preg_split('/\r?\n/', $text) as $line) {
        if (str_starts_with($line, 'windows_cpu_time_total{')) {
            if (preg_match('/mode="([^"]+)"[^}]*}\s+([0-9eE+.-]+)/', $line, $m)) {
                $v=(float)$m[2]; $total += $v; if ($m[1]==='idle') $idle += $v;
            }
        } elseif (str_starts_with($line, 'windows_memory_physical_total_bytes ')) {
            $parts=preg_split('/\s+/',trim($line)); $memTotal=(float)($parts[1]??0);
        } elseif (str_starts_with($line, 'windows_memory_physical_free_bytes ')) {
            $parts=preg_split('/\s+/',trim($line)); $memFree=(float)($parts[1]??0);
        }
    }
    return ['idle'=>$idle,'total'=>$total,'memTotal'=>$memTotal,'memFree'=>$memFree];
}

function fetchWindowsExporterText()
{
    $url = 'http://127.0.0.1:9182/metrics';
    $ch = @curl_init($url);
    if (!$ch) return null;
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 1.2,
        CURLOPT_CONNECTTIMEOUT => 0.5,
        CURLOPT_HTTPHEADER => ['Accept: text/plain'],
    ]);
    $text = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($text === false || $code !== 200) return null;
    return $text;
}

function queryWindowsExporterDirect()
{
    // windows_cpu_time_total is a cumulative counter since boot, so a single
    // snapshot only ever yields the all-time-average CPU usage (a number that
    // barely moves and doesn't reflect current load). Take two samples a short
    // moment apart and use the delta, the same way collectLinuxLocal() does for
    // /proc/stat, so this fallback reports real current usage instead of noise.
    $textA = fetchWindowsExporterText();
    if ($textA === null) return null;
    $sampleA = readWindowsExporterSample($textA);

    usleep(200000);

    $textB = fetchWindowsExporterText();
    if ($textB === null) return null;
    $sampleB = readWindowsExporterSample($textB);

    if ($sampleB['total'] <= 0 && $sampleB['memTotal'] <= 0) return null;

    $dTotal = $sampleB['total'] - $sampleA['total'];
    $dIdle = $sampleB['idle'] - $sampleA['idle'];
    $cpu = $dTotal > 0 ? (1 - ($dIdle / $dTotal)) * 100 : 0;

    $memTotal = $sampleB['memTotal'];
    $memFree = $sampleB['memFree'];
    $used = max(0, $memTotal - $memFree);

    return ['cpu'=>clampPercent($cpu),'ram'=>clampPercent($memTotal>0?($used/$memTotal)*100:0),
        'memory_total'=>$memTotal,'memory_used'=>$used,'memory_free'=>$memFree,
        'source'=>'windows_exporter','family'=>'windows','instance'=>localHostname().' (127.0.0.1:9182)',
        'job'=>'windows_exporter','up'=>1,'os'=>'windows'];
}

function discoverPrometheusTarget()
{
    // ERMonitor defaults to the Windows exporter configured by Prometheus.
    // Prefer job="windows" so discovery stays fast and deterministic.
    $data = prometheusSafe('up{job="windows"}');
    if (!$data) {
        $data = prometheusSafe('up');
    }
    if (!$data) return null;

    $results = $data['data']['result'] ?? [];
    if (!$results) return null;

    $best = null;
    foreach ($results as $row) {
        $metric = $row['metric'] ?? [];
        $instance = (string) ($metric['instance'] ?? '');
        $job = (string) ($metric['job'] ?? '');
        $up = (float) ($row['value'][1] ?? 0);
        $score = ($up >= 1 ? 20 : 0);
        if ($job === 'windows') $score += 20;
        if (preg_match('/localhost|127\.0\.0\.1/i', $instance)) $score += 8;
        if (!$best || $score > $best['score']) {
            $best = ['instance'=>$instance,'job'=>$job,'up'=>$up>=1?1:0,'score'=>$score];
        }
    }
    return $best;
}

function collectPrometheusMetrics($target)
{
    $instance = (string) ($target['instance'] ?? '');
    $job = (string) ($target['job'] ?? '');
    if ($instance === '') return null;

    // One PromQL request returns CPU + RAM + memory totals together.
    // This is substantially cheaper than repeatedly probing metric families.
    $selector = 'instance="' . addslashes($instance) . '"';
    if ($job !== '') $selector .= ',job="' . addslashes($job) . '"';

    $windowsQuery = '100 - (avg by (instance) (rate(windows_cpu_time_total{' . $selector . ',mode="idle"}[2m])) * 100)'
        . ' or windows_memory_physical_total_bytes{' . $selector . '}'
        . ' or windows_memory_physical_free_bytes{' . $selector . '}';
    $data = prometheusSafe($windowsQuery);

    if ($data) {
        $cpu = 0; $total = 0; $free = 0; $found = false;
        foreach (($data['data']['result'] ?? []) as $row) {
            $metric = $row['metric'] ?? [];
            $name = $metric['__name__'] ?? '';
            $value = (float) ($row['value'][1] ?? 0);
            if ($name === 'windows_memory_physical_total_bytes') { $total = $value; $found = true; }
            elseif ($name === 'windows_memory_physical_free_bytes') { $free = $value; $found = true; }
            else { $cpu = $value; $found = true; }
        }
        if ($found && ($total > 0 || $cpu > 0)) {
            $used = max(0, $total - $free);
            $ram = $total > 0 ? ($used / $total) * 100 : 0;
            return [
                'cpu'=>clampPercent($cpu), 'ram'=>clampPercent($ram),
                'memory_total'=>$total, 'memory_used'=>$used, 'memory_free'=>$free,
                'source'=>'prometheus', 'family'=>'windows', 'instance'=>$instance,
                'job'=>$job, 'up'=>(int)($target['up'] ?? 0), 'os'=>'windows'
            ];
        }
    }

    // Fallback for node_exporter installations.
    $nodeQuery = '100 - (avg by (instance) (rate(node_cpu_seconds_total{' . $selector . ',mode="idle"}[2m])) * 100)'
        . ' or node_memory_MemTotal_bytes{' . $selector . '}'
        . ' or node_memory_MemAvailable_bytes{' . $selector . '}';
    $data = prometheusSafe($nodeQuery);
    if (!$data) return null;

    $cpu=0; $total=0; $available=0; $found=false;
    foreach (($data['data']['result'] ?? []) as $row) {
        $metric=$row['metric']??[]; $name=$metric['__name__']??''; $value=(float)($row['value'][1]??0);
        if ($name==='node_memory_MemTotal_bytes') {$total=$value;$found=true;}
        elseif ($name==='node_memory_MemAvailable_bytes') {$available=$value;$found=true;}
        else {$cpu=$value;$found=true;}
    }
    if (!$found || ($total<=0 && $cpu<=0)) return null;
    $used=max(0,$total-$available); $ram=$total>0?($used/$total)*100:0;
    return [
        'cpu'=>clampPercent($cpu), 'ram'=>clampPercent($ram),
        'memory_total'=>$total, 'memory_used'=>$used, 'memory_free'=>$available,
        'source'=>'prometheus', 'family'=>'node', 'instance'=>$instance,
        'job'=>$job, 'up'=>(int)($target['up'] ?? 0), 'os'=>'linux'
    ];
}

function buildOsLabel($os)
{
    $pretty = php_uname('s') . ' ' . php_uname('r');

    if ($os === 'windows') {
        return trim($pretty);
    }

    if (is_readable('/etc/os-release')) {
        $text = (string) file_get_contents('/etc/os-release');
        if (preg_match('/PRETTY_NAME="?([^"\n]+)/', $text, $m)) {
            return $m[1];
        }
    }

    return $pretty;
}

function recordAlertTransition($conn, $serverId, $name, $previousStatus, $status)
{
    if ($previousStatus === $status) {
        return;
    }

    // Step 7 event storage is optional until the database migration is run.
    // Never let a missing/mismatched event table break 1-second telemetry.
    try {
        $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'alert_events'");
        if (!$tableCheck || mysqli_num_rows($tableCheck) === 0) {
            return;
        }
    } catch (Throwable $e) {
        return;
    }

    $isTriggered = in_array($status, ['warning', 'offline'], true);
    $isRecovered = $status === 'online' && in_array($previousStatus, ['warning', 'offline'], true);

    if (!$isTriggered && !$isRecovered) {
        return;
    }

    $eventType = $isRecovered ? 'recovered' : 'triggered';
    $message = $isRecovered
        ? 'Server kembali online.'
        : ($status === 'offline' ? 'Server tidak dapat dipantau.' : 'Resource server melewati ambang warning.');

    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO alert_events (server_id, server_name, previous_status, status, event_type, message) VALUES (?, ?, ?, ?, ?, ?)'
    );
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'isssss', $serverId, $name, $previousStatus, $status, $eventType, $message);
        @mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

function syncLocalServer($payload)
{
    $dbFile = __DIR__ . '/../config/database.php';
    if (!is_file($dbFile)) {
        return null;
    }

    require_once $dbFile;
    if (!isset($conn) || !$conn) {
        return null;
    }

    $name = $payload['name'];
    $ip = $payload['ip'];
    $os = $payload['os'];
    $status = $payload['status'];
    $description = 'Auto-discovered local host';

    $sql = 'SELECT id, status FROM servers WHERE name = ? OR ip_address = ? LIMIT 1';
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ss', $name, $ip);
    mysqli_stmt_execute($stmt);
    $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($existing) {
        $id = (int) $existing['id'];
        $previousStatus = $existing['status'];
        $update = mysqli_prepare(
            $conn,
            'UPDATE servers SET os = ?, status = ?, description = ? WHERE id = ?'
        );
        mysqli_stmt_bind_param($update, 'sssi', $os, $status, $description, $id);
        mysqli_stmt_execute($update);
        recordAlertTransition($conn, $id, $name, $previousStatus, $status);
        return $id;
    }

    $insert = mysqli_prepare(
        $conn,
        'INSERT INTO servers (name, ip_address, os, status, description) VALUES (?, ?, ?, ?, ?)'
    );
    mysqli_stmt_bind_param($insert, 'sssss', $name, $ip, $os, $status, $description);
    if (!mysqli_stmt_execute($insert)) {
        return null;
    }

    $id = (int) mysqli_insert_id($conn);
    if (in_array($status, ['warning', 'offline'], true)) {
        recordAlertTransition($conn, $id, $name, null, $status);
    }
    return $id;
}

try {
    $hostname = localHostname();
    $ip = localIpAddress();
    $osFamily = detectOsFamily();
    $osLabel = buildOsLabel($osFamily);

    $target = discoverPrometheusTarget();
    $remote = $target ? collectPrometheusMetrics($target) : null;

    // Fast local fallback: read windows_exporter directly. This avoids launching
    // PowerShell/WMI on every 1-second request when Prometheus is temporarily down.
    if (!is_array($remote)) {
        $remote = queryWindowsExporterDirect();
    }

    // Do not run PowerShell/WMI unless both Prometheus and windows_exporter are unavailable.
    $useRemote = is_array($remote) && (($remote['up'] ?? 0) === 1 || ($remote['cpu'] ?? 0) > 0 || ($remote['ram'] ?? 0) > 0);
    $metrics = $useRemote ? $remote : collectLocalMetrics();
    $source = $metrics['source'] ?? 'local';

    $cpu = clampPercent($metrics['cpu'] ?? 0);
    $ram = clampPercent($metrics['ram'] ?? 0);
    $totalBytes = (float) ($metrics['memory_total'] ?? 0);
    $usedBytes = (float) ($metrics['memory_used'] ?? 0);
    $freeBytes = (float) ($metrics['memory_free'] ?? 0);
    $disk = collectDisk();

    $cpuStatus = getResourceStatus($cpu);
    $ramStatus = getResourceStatus($ram);

    $probeUp = $source === 'prometheus'
        ? (int) ($metrics['up'] ?? 0)
        : 1;

    if ($probeUp !== 1) {
        $serverStatus = 'offline';
    } elseif ($cpu >= 75 || $ram >= 75) {
        $serverStatus = 'warning';
    } else {
        $serverStatus = 'online';
    }

    $instance = $source === 'prometheus'
        ? ($metrics['instance'] ?? 'prometheus')
        : $hostname . ' (' . $ip . ')';

    $probe = $source === 'prometheus'
        ? (($metrics['job'] ?? 'exporter') . ' · ' . ($metrics['family'] ?? 'auto'))
        : 'local agent · ' . $osFamily;

    syncLocalServer([
        'name' => $hostname,
        'ip' => $ip,
        'os' => $osLabel,
        'status' => $serverStatus,
    ]);

    $fleet = ['total' => 0, 'online' => 0, 'warning' => 0, 'offline' => 0];
    $dbFile = __DIR__ . '/../config/database.php';
    if (is_file($dbFile)) {
        require_once $dbFile;
        if (isset($conn) && $conn) {
            $fleetResult = mysqli_query($conn, "SELECT COUNT(*) total, SUM(status='online') online, SUM(status='warning') warning, SUM(status='offline') offline FROM servers");
            if ($fleetResult) {
                $fleetRow = mysqli_fetch_assoc($fleetResult) ?: [];
                foreach ($fleet as $key => $value) {
                    $fleet[$key] = (int)($fleetRow[$key] ?? 0);
                }
            }
        }
    }

    echo json_encode([
        'success' => true,
        'timestamp' => date('c'),
        'fleet' => $fleet,
        'server' => [
            'name' => $hostname,
            'ip' => $ip,
            'os' => $osLabel,
            'family' => $osFamily,
            'instance' => $instance,
            'probe' => $probe,
            'source' => $source,
            'up' => $probeUp,
            'status' => $serverStatus,
        ],
        'cpu' => [
            'usage' => round($cpu, 2),
            'status' => $cpuStatus,
        ],
        'ram' => [
            'usage' => round($ram, 2),
            'status' => $ramStatus,
            'total_gb' => bytesToGb($totalBytes),
            'used_gb' => bytesToGb($usedBytes),
            'free_gb' => bytesToGb($freeBytes),
        ],
        'disk' => $disk,
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('c'),
    ], JSON_PRETTY_PRINT);
}
