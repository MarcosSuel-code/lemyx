<canvas id="graficoCategorias" width="400" height="200"></canvas>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('graficoCategorias');
new Chart(ctx, {
    type: 'pie',
    data: {
        labels: ['Categoria A', 'Categoria B', 'Categoria C'],
        datasets: [{
            label: 'Produtos por Categoria',
            data: [10, 20, 30], // Trocar pelos dados reais do PHP
            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56'],
        }]
    },
});
</script>