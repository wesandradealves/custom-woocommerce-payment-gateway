document.addEventListener('DOMContentLoaded', function () {
    const interval = setInterval(() => {
        const originCells = document.querySelectorAll('td[data-colname="Origin"]');

        if (!originCells.length) return;

        originCells.forEach(cell => {
            const totalCell = cell.previousElementSibling?.previousElementSibling;
            let currencySymbol;

            if(totalCell) {
                currencySymbol = totalCell.querySelector('.woocommerce-Price-currencySymbol');

                if(currencySymbol.getHTML() && currencySymbol.getHTML() === 'BDM') {
                    cell.textContent = 'BDM Checkout';
                }
            }
        });
        
        clearInterval(interval); 
    });
});
