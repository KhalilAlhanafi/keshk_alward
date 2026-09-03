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
        items: (window.__INITIAL_WISHLIST__ || []).map(Number),
        
        has(productId) {
            return this.items.includes(Number(productId));
        },
        
        setItems(newItems) {
            this.items = (newItems || []).map(Number);
        },

        async toggle(productId) {
            const id = Number(productId);
            if (!id) return;

            const exists = this.has(id);
            
            // 1. Optimistic UI update
            if (exists) {
                this.items = this.items.filter(i => i !== id);
            } else {
                this.items.push(id);
            }

            // 2. Server mutation
            try {
                const csrfToken = document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '';
                const response = await fetch('/wishlist/toggle', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ product_id: id })
                });

                const data = await response.json();
                if (response.ok) {
                    if (data.status === 'added' && !this.has(id)) {
                        this.items.push(id);
                    } else if (data.status === 'removed' && this.has(id)) {
                        this.items = this.items.filter(i => i !== id);
                    }

                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { 
                            message: data.message || (data.status === 'added' ? 'تمت إضافة الباقة إلى المفضلة' : 'تمت إزالة الباقة من المفضلة'), 
                            type: 'success' 
                        } 
                    }));
                } else {
                    // Rollback
                    if (exists) {
                        this.items.push(id);
                    } else {
                        this.items = this.items.filter(i => i !== id);
                    }
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { message: data.message || 'تعذر تحديث المفضلة', type: 'error' } 
                    }));
                }
            } catch (err) {
                // Rollback
                if (exists) {
                    this.items.push(id);
                } else {
                    this.items = this.items.filter(i => i !== id);
                }
                window.dispatchEvent(new CustomEvent('toast', { 
                    detail: { message: 'حدث خطأ في الاتصال بالسيرفر', type: 'error' } 
                }));
            }
        }
    });
});

window.Alpine = Alpine;

Alpine.start();
