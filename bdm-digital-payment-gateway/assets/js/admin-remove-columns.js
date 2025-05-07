document.addEventListener('DOMContentLoaded', function () {
    const interval = setInterval(() => {
        const totalCells = document.querySelectorAll('td[data-colname="Total"]');
        const originCells = document.querySelectorAll('td[data-colname="Origin"]');

        if (!totalCells.length && !originCells.length) return;

        // Trocar moeda de R$ para BDM
        totalCells.forEach(cell => {
            const originalText = cell.textContent.trim();
            const modifiedText = originalText.replace(/^R\$\s?/, 'BDM ');
            cell.textContent = modifiedText;
        });

        // Substituir conteúdo da coluna "Origin"
        originCells.forEach(cell => {
            cell.textContent = 'BDM Digital';
        });

        clearInterval(interval); 
    });
});
