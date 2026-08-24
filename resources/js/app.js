import './bootstrap';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import persist from '@alpinejs/persist';

Alpine.plugin(collapse);
Alpine.plugin(persist);

// Global money formatter helper - always uses Western Arabic numerals
window.formatMoney = function(amount) {
    // Convert to number if it's a string
    const numAmount = typeof amount === 'string' ? parseInt(amount, 10) : amount;
    
    // Format with Western Arabic numerals and commas
    const formatted = new Intl.NumberFormat('en-US').format(numAmount);
    
    return `${formatted} ل.س`;
};

// Register Alpine Global Stores
document.addEventListener('alpine:init', () => {
    Alpine.store('cart', {
        count: window.__INITIAL_CART_COUNT__ || 0,
        items: [],
        setCount(newCount) {
            this.count = newCount;
        },
        increment(by = 1) {
            this.count += by;
        }
    });

    Alpine.store('wishlist', {
        items: window.__INITIAL_WISHLIST__ || [],
        toggle(productId) {
            const index = this.items.indexOf(productId);
            if (index > -1) {
                this.items.splice(index, 1);
            } else {
                this.items.push(productId);
            }
        },
        has(productId) {
            return this.items.includes(productId);
        }
    });
});

window.Alpine = Alpine;

Alpine.start();
