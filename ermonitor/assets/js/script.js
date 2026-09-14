/* ERMonitor — realtime control room */
const MONITORING_API = document.body?.dataset?.api || "../api/monitoring.php";
const UPDATE_INTERVAL = 1000;
const MAX_DATA_POINTS = 30;
const GAUGE_C = 2 * Math.PI * 52;
let cpuUsage = 0, ramUsage = 0, isFetching = false, lastServerStatus = "offline";
let hasSample = false;
const SMOOTHING = 0.35; // 0-1: lower = smoother/slower, higher = snappier/more raw
const labels = [], cpuData = [], ramData = [];
const el = id => document.getElementById(id);
function nowTime(){return new Date().toLocaleTimeString("id-ID",{hour:"2-digit",minute:"2-digit",second:"2-digit"});}
function clamp(v){const n=Number(v);return Number.isFinite(n)?Math.max(0,Math.min(100,n)):0;}
function fmt(v){return clamp(v).toFixed(1);}
function setGauge(id,value,color){const c=el(id);if(!c)return;c.setAttribute("stroke-dasharray",GAUGE_C);c.setAttribute("stroke-dashoffset",GAUGE_C*(1-value/100));if(color)c.setAttribute("stroke",color);}
function gaugeColor(v,normal){return v>=90?"#fb7185":v>=75?"#fbbf24":normal;}
function updateCPUUI(){const v=clamp(cpuUsage);if(el("cpuValue"))el("cpuValue").textContent=fmt(v);if(el("cpuBar"))el("cpuBar").style.width=v+"%";if(el("cpuBarLabel"))el("cpuBarLabel").textContent=fmt(v)+"%";if(el("cpuStatusLabel"))el("cpuStatusLabel").textContent=v>=90?"critical":v>=75?"warning":"healthy";if(el("cpuHealth"))el("cpuHealth").textContent=v>=90?"critical":v>=75?"warning":"healthy";setGauge("cpuGauge",v,gaugeColor(v,"#2ee9d0"));el("cpuBar")?.parentElement?.parentElement?.classList.toggle("resource-hot",v>=90);}
function updateRAMUI(){const v=clamp(ramUsage);if(el("ramValue"))el("ramValue").textContent=fmt(v);if(el("ramBar"))el("ramBar").style.width=v+"%";if(el("ramBarLabel"))el("ramBarLabel").textContent=fmt(v)+"%";if(el("ramHealth"))el("ramHealth").textContent=v>=90?"critical":v>=75?"warning":"healthy";setGauge("ramGauge",v,gaugeColor(v,"#a78bfa"));el("ramBar")?.parentElement?.parentElement?.classList.toggle("resource-hot",v>=90);}
function updateLastUpdate(){const node=el("lastUpdate");if(!node)return;node.textContent=nowTime();node.classList.remove("sync-flash");void node.offsetWidth;node.classList.add("sync-flash");}
function tickClock(){if(el("liveClock"))el("liveClock").textContent=nowTime();}
function updateFleet(f){if(!f)return;[["totalServers",f.total],["onlineServers",f.online],["warningServers",f.warning],["offlineServers",f.offline]].forEach(([id,v])=>{if(el(id))el(id).textContent=Number(v)||0;});}
let usageChart=null;const chartCanvas=el("usageChart")||el("resourceChart");
if(false && chartCanvas&&typeof Chart!=="undefined"){
 usageChart=new Chart(chartCanvas.getContext("2d"),{type:"line",data:{labels,datasets:[
 {label:"CPU",data:cpuData,borderColor:"#2ee9d0",backgroundColor:"rgba(46,233,208,.12)",borderWidth:2,tension:.35,pointRadius:0,pointHoverRadius:4,fill:true},
 {label:"RAM",data:ramData,borderColor:"#a78bfa",backgroundColor:"rgba(167,139,250,.12)",borderWidth:2,tension:.35,pointRadius:0,pointHoverRadius:4,fill:true}]},options:{responsive:true,maintainAspectRatio:false,animation:{duration:350,easing:"easeOutQuart"},interaction:{intersect:false,mode:"index"},plugins:{legend:{labels:{color:"#8b9bb4",font:{size:11,family:"IBM Plex Mono"},boxWidth:10}},tooltip:{callbacks:{label:c=>" "+c.dataset.label+": "+Number(c.parsed.y).toFixed(1)+"%"}}},scales:{x:{ticks:{color:"#5b6b82",maxTicksLimit:8,font:{size:10}},grid:{color:"rgba(46,233,208,.06)"}},y:{min:0,max:100,ticks:{color:"#5b6b82",callback:v=>v+"%"},grid:{color:"rgba(46,233,208,.06)"}}}}});
}
let chartSize={w:0,h:0,dpr:0};
function prepareChartCanvas(c){
 const dpr=window.devicePixelRatio||1,r=c.getBoundingClientRect();
 const w=Math.max(320,Math.round(r.width)),h=Math.max(180,Math.round(r.height));
 const ctx=c.getContext("2d");
 if(w!==chartSize.w||h!==chartSize.h||dpr!==chartSize.dpr){c.width=w*dpr;c.height=h*dpr;chartSize={w,h,dpr};ctx.setTransform(dpr,0,0,dpr,0,0);}
 return {w,h,ctx};
}
function drawLightChart(){
 const c=el("usageChart"); if(!c) return;
 const {w,h,ctx:x}=prepareChartCanvas(c);
 x.clearRect(0,0,w,h);
 const padL=42,padR=14,padT=16,padB=24,plotW=w-padL-padR,plotH=h-padT-padB;
 x.font="10px 'IBM Plex Mono',monospace"; x.fillStyle="#5b6b82"; x.strokeStyle="rgba(46,233,208,.08)"; x.lineWidth=1;
 for(let i=0;i<=4;i++){const y=padT+plotH*i/4;x.beginPath();x.moveTo(padL,y);x.lineTo(w-padR,y);x.stroke();x.fillText((100-i*25)+"%",4,y+3);}
 if(labels.length>1){
  x.textAlign="center";
  [0,Math.floor((labels.length-1)/2),labels.length-1].forEach(i=>{
   const px=padL+plotW*(i/Math.max(1,MAX_DATA_POINTS-1));
   x.fillText(labels[i]||"",px,h-6);
  });
  x.textAlign="left";
 }
 const toXY=(i,v)=>[padL+plotW*(i/Math.max(1,MAX_DATA_POINTS-1)),padT+plotH*(1-clamp(v)/100)];
 const plot=(arr,stroke)=>{
  if(arr.length<1)return;
  x.save(); x.strokeStyle=stroke; x.fillStyle=stroke; x.lineWidth=2; x.lineJoin="round"; x.lineCap="round";
  x.shadowColor=stroke; x.shadowBlur=5;
  if(arr.length===1){
   const [px,py]=toXY(0,arr[0]); x.beginPath(); x.arc(px,py,2.5,0,Math.PI*2); x.fill();
  } else {
   x.beginPath();
   arr.forEach((v,i)=>{const [px,py]=toXY(i,v); i?x.lineTo(px,py):x.moveTo(px,py);});
   x.stroke();
   const [lx,ly]=toXY(arr.length-1,arr[arr.length-1]);
   x.beginPath(); x.arc(lx,ly,3,0,Math.PI*2); x.fill();
  }
  x.restore();
 };
 plot(cpuData,"#2ee9d0"); plot(ramData,"#a78bfa");
}
let chartTick=0;
function addChartData(cpu,ram){
 labels.push(nowTime()); cpuData.push(clamp(cpu)); ramData.push(clamp(ram));
 while(labels.length>MAX_DATA_POINTS){labels.shift();cpuData.shift();ramData.shift();}
 if(usageChart){usageChart.update();return;}
 chartTick++;
 if(chartTick===1||chartTick%2===0) drawLightChart(); // first point instantly, then every ~2s: smoother, no per-second reflow
}
function updateServerStatus(status){const normalized=["online","warning","offline"].includes(status)?status:"offline";lastServerStatus=normalized;const s=el("serverStatus");if(s){s.className="pill pill-"+normalized;s.textContent=normalized.toUpperCase();}document.body.dataset.serverStatus=normalized;document.documentElement.classList.toggle("server-warning",normalized==="warning");document.documentElement.classList.toggle("server-offline",normalized==="offline");}
function setTelemetryStatus(serverStatus){const status=el("monitoringStatus"),live=el("monitoringLive"),chip=el("connectionChip");const offline=serverStatus==="offline";const warning=serverStatus==="warning";if(status){status.textContent=offline?"OFFLINE":warning?"WARNING":"LIVE";status.classList.toggle("offline",offline);status.classList.toggle("online",!offline);}if(live){live.classList.toggle("is-offline",offline);live.innerHTML='<span class="pulse-dot"></span> '+(offline?"OFFLINE":warning?"WARNING":"LIVE");}if(chip)chip.classList.toggle("is-offline",offline);}
function setApiError(){setTelemetryStatus("offline");updateServerStatus("offline");document.body.classList.remove("api-live");document.body.classList.add("api-error");}
function trendText(arr){
 if(arr.length<5) return "Mengumpulkan data…";
 const recent=arr.slice(-5), older=arr.slice(-10,-5);
 const a=recent.reduce((x,y)=>x+y,0)/recent.length, b=older.reduce((x,y)=>x+y,0)/older.length;
 const d=a-b; return d>3?"Tren naik":d<-3?"Tren turun":"Stabil";
}
function updateAnalysis(){
 const c=clamp(cpuUsage), r=clamp(ramUsage), status=lastServerStatus;
 const set=(id,v)=>{if(el(id))el(id).textContent=v};
 set("analysisCpu",fmt(c)+"% · "+trendText(cpuData)); set("analysisRam",fmt(r)+"% · "+trendText(ramData));
 set("analysisCpuText",c>=90?"Beban sangat tinggi":c>=75?"Beban tinggi":"Beban normal");
 set("analysisRamText",r>=90?"Memory sangat tinggi":r>=75?"Memory tinggi":"Memory normal");
 const overall=status==="offline"?"OFFLINE":(c>=75||r>=75||status==="warning")?"WARNING":"HEALTHY";
 set("analysisOverall",overall); set("analysisOverallText",overall==="HEALTHY"?"Host berjalan normal":"Periksa resource atau koneksi exporter");
 const a=el("analysisStatus"); if(a){a.className="pill pill-"+(overall==="HEALTHY"?"online":overall==="WARNING"?"warning":"offline");a.textContent=overall;}
}
async function fetchMonitoringData(){if(isFetching)return;isFetching=true;try{const response=await fetch(MONITORING_API+"?t="+Date.now(),{cache:"no-store",headers:{Accept:"application/json"}});if(!response.ok)throw new Error("HTTP Error "+response.status);const data=await response.json();if(!data.success)throw new Error(data.error||"Monitoring API gagal.");
 updateFleet(data.fleet);
 const rawCpu=clamp(data.cpu?.usage), rawRam=clamp(data.ram?.usage);
 // Smooth incoming readings (EMA) so a one-off noisy sample doesn't make the
 // number jump abruptly; first real reading snaps immediately, after that it
 // eases toward the new value instead of teleporting to it.
 cpuUsage = hasSample ? cpuUsage + SMOOTHING*(rawCpu-cpuUsage) : rawCpu;
 ramUsage = hasSample ? ramUsage + SMOOTHING*(rawRam-ramUsage) : rawRam;
 hasSample = true;
 updateCPUUI();updateRAMUI();
 if(el("ramMeta")&&data.ram){const used=Number(data.ram.used_gb),total=Number(data.ram.total_gb);if(Number.isFinite(used)&&Number.isFinite(total))el("ramMeta").textContent=used.toFixed(1)+" / "+total.toFixed(1)+" GB";}
 if(el("nodeName"))el("nodeName").textContent=data.server?.name||"—";if(el("nodeIp"))el("nodeIp").textContent=data.server?.ip||"—";if(el("nodeInstance"))el("nodeInstance").textContent=data.server?.instance||"—";if(el("nodeUp"))el("nodeUp").textContent=data.server?.up===1?"up":"down";if(el("telemetrySource"))el("telemetrySource").textContent=(data.server?.source||"telemetry")+" · "+(data.server?.probe||"auto");
 addChartData(cpuUsage,ramUsage);updateServerStatus(data.server?.status||"online");setTelemetryStatus(data.server?.status||"online");updateAnalysis();updateLastUpdate();document.body.classList.remove("api-error");document.body.classList.add("api-live");
 }catch(error){console.error("ERMonitor monitoring error:",error);setApiError();updateAnalysis();}finally{isFetching=false;}}
async function refreshData(){const b=el("refreshBtn");if(b){b.disabled=true;b.classList.add("refreshing");b.setAttribute("aria-busy","true");}try{await fetchMonitoringData();}finally{setTimeout(()=>{if(b){b.classList.remove("refreshing");b.removeAttribute("aria-busy");b.disabled=false;}},450);}}
el("refreshBtn")?.addEventListener("click",refreshData);
const menuToggle=el("menuToggle"),sidebar=el("sidebar");if(menuToggle&&sidebar){menuToggle.addEventListener("click",()=>{const open=sidebar.classList.toggle("is-open");menuToggle.setAttribute("aria-expanded",String(open));document.body.classList.toggle("menu-open",open);});document.querySelectorAll(".nav-link").forEach(link=>link.addEventListener("click",()=>{sidebar.classList.remove("is-open");document.body.classList.remove("menu-open");menuToggle.setAttribute("aria-expanded","false");}));}
document.addEventListener("keydown",e=>{const tag=document.activeElement?.tagName?.toLowerCase();if(e.key.toLowerCase()==="r"&&!['input','textarea','select'].includes(tag)){e.preventDefault();refreshData();}if(e.key==="Escape"&&sidebar){sidebar.classList.remove("is-open");document.body.classList.remove("menu-open");menuToggle?.setAttribute("aria-expanded","false");}});
window.addEventListener("resize",()=>drawLightChart());
setInterval(fetchMonitoringData,UPDATE_INTERVAL);setInterval(tickClock,1000);tickClock();fetchMonitoringData();
