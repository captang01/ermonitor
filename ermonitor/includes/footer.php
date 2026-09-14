    </section>
</main>
</div>
<script src="<?= htmlspecialchars($rootPath ?? '') ?>/assets/js/icons.js"></script>
<script src="<?= htmlspecialchars($rootPath ?? '') ?>/assets/js/script.js"></script>
<script>
if ("serviceWorker" in navigator) { navigator.serviceWorker.register("<?= htmlspecialchars($rootPath ?? '') ?>/sw.js").catch(() => {}); }
</script>
</body>
</html>
