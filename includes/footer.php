</main>

<?php /* Fecha a área principal e carrega os scripts compartilhados no fim da página. */ ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://vlibras.gov.br/app/vlibras-plugin.js" onerror="document.getElementById('accessibilityStatus').textContent = 'Não foi possível carregar o tradutor de Libras. Verifique sua conexão com a internet.'; console.error('Falha ao carregar o script oficial do VLibras.')"></script>
<script src="/igor_tcc_teste/assets/js/app.js?v=8"></script>
<?php if (isset($extraJs)) { echo $extraJs; } ?>
</body>
</html>
