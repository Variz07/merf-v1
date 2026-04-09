// Filter functionality for category pages
document.addEventListener('DOMContentLoaded', function() {
    // Mobile filter toggle
    const filterToggle = document.querySelector('.filter-toggle');
    const filterSidebar = document.querySelector('.sidebar');
    
    if(filterToggle && filterSidebar) {
        filterToggle.addEventListener('click', function() {
            filterSidebar.classList.toggle('active');
            document.body.style.overflow = filterSidebar.classList.contains('active') ? 'hidden' : '';
        });
    }
    
    // Price range slider (if exists)
    const priceRange = document.querySelector('.price-range');
    if(priceRange) {
        const minInput = document.querySelector('input[name="min_price"]');
        const maxInput = document.querySelector('input[name="max_price"]');
        const minValue = document.querySelector('.min-value');
        const maxValue = document.querySelector('.max-value');
        
        priceRange.addEventListener('input', function(e) {
            const value = e.target.value;
            if(e.target.classList.contains('min-range')) {
                minValue.textContent = formatCurrency(value);
                if(minInput) minInput.value = value;
            } else {
                maxValue.textContent = formatCurrency(value);
                if(maxInput) maxInput.value = value;
            }
        });
    }
    
    // Clear filters
    const clearFiltersBtn = document.querySelector('.clear-filters');
    if(clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            const currentUrl = new URL(window.location.href);
            const params = new URLSearchParams(currentUrl.search);
            
            // Keep only essential params
            const keepParams = ['category', 'sort'];
            for(let key of params.keys()) {
                if(!keepParams.includes(key)) {
                    params.delete(key);
                }
            }
            
            window.location.href = currentUrl.pathname + '?' + params.toString();
        });
    }
    
    // Apply filters with debounce
    let filterTimeout;
    const filterInputs = document.querySelectorAll('.filter-input, .filter-select');
    
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(function() {
                document.querySelector('.filter-form')?.submit();
            }, 500);
        });
    });
});

function formatCurrency(value) {
    return 'Rp ' + parseInt(value).toLocaleString('id-ID');
}