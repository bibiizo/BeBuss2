/**
 * BeBuss JavaScript Module
 * Main application functionality
 */

// Application configuration
const BeBussConfig = {
    debounceDelay: 300,
    autocompleteMinLength: 2,
    profileComplete: false,
    baseUrl: '/Proyek%20PBW/BeBuss'
};

// DOM Elements cache
const Elements = {
    get resultArea() { return document.getElementById('result-area'); },
    get fromInput() { return document.getElementById('from'); },
    get toInput() { return document.getElementById('to'); },
    get dateInput() { return document.getElementById('date'); },
    get fromAutocomplete() { return document.getElementById('from-autocomplete-list'); },
    get toAutocomplete() { return document.getElementById('to-autocomplete-list'); },
    get profileModal() { return document.getElementById('profileModal'); }
};

// Utility functions
const Utils = {
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    sanitizeInput(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },

    formatCurrency(amount) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    },

    formatDate(dateString) {
        return new Intl.DateTimeFormat('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        }).format(new Date(dateString));
    }
};

// Modal management
const Modal = {
    show(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    },

    hide(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    },

    init() {
        // Close modal when clicking outside
        window.addEventListener('click', (event) => {
            if (event.target.classList.contains('modal')) {
                Modal.hide(event.target.id);
            }
        });

        // Close modal with escape key
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                document.querySelectorAll('.modal').forEach(modal => {
                    if (modal.style.display === 'block') {
                        Modal.hide(modal.id);
                    }
                });
            }
        });

        // Handle close buttons
        document.querySelectorAll('.close').forEach(closeBtn => {
            closeBtn.addEventListener('click', () => {
                const modal = closeBtn.closest('.modal');
                if (modal) Modal.hide(modal.id);
            });
        });
    }
};

// Profile management
const Profile = {
    checkCompletion(callback) {
        if (!BeBussConfig.profileComplete) {
            Modal.show('profileModal');
            return false;
        }
        if (callback) callback();
        return true;
    },

    showIncompleteAlert() {
        Modal.show('profileModal');
    }
};

// Search functionality with live search and autocomplete
const Search = {
    debouncedUpdate: null,

    init() {
        console.log('🔍 Search.init() called');
        this.debouncedUpdate = Utils.debounce(this.updateResults.bind(this), BeBussConfig.debounceDelay);
        
        // Add event listeners for live search
        if (Elements.fromInput) {
            console.log('✅ Adding event listeners to from input');
            Elements.fromInput.addEventListener('input', this.debouncedUpdate);
            Elements.fromInput.addEventListener('keydown', (e) => {
                if (e.keyCode === 13) { // Enter key
                    e.preventDefault();
                    this.updateResults();
                }
            });
        } else {
            console.warn('❌ From input not found');
        }
        
        if (Elements.toInput) {
            console.log('✅ Adding event listeners to to input');
            Elements.toInput.addEventListener('input', this.debouncedUpdate);
            Elements.toInput.addEventListener('keydown', (e) => {
                if (e.keyCode === 13) { // Enter key
                    e.preventDefault();
                    this.updateResults();
                }
            });
        } else {
            console.warn('❌ To input not found');
        }
        
        if (Elements.dateInput) {
            console.log('✅ Adding event listener to date input');
            Elements.dateInput.addEventListener('change', this.updateResults);
        } else {
            console.warn('❌ Date input not found');
        }

        // Initial load - always show results on page load
        console.log('🚀 Calling initial updateResults()');
        this.updateResults();
    },

    updateResults() {
        console.log('🔄 updateResults() called');
        if (!Elements.resultArea) {
            console.error('❌ Result area not found');
            return;
        }

        const params = new URLSearchParams({
            from: Elements.fromInput?.value || '',
            to: Elements.toInput?.value || '',
            date: Elements.dateInput?.value || '',
            profile_complete: BeBussConfig.profileComplete ? '1' : '0'
        });

        console.log('📝 Search params:', params.toString());
        console.log('🌐 Fetching po_list.php...');

        fetch(`po_list.php?${params}`)
            .then(response => {
                console.log('📡 Response status:', response.status);
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return response.text();
            })
            .then(data => {
                console.log('✅ Data received:', data.length, 'characters');
                Elements.resultArea.innerHTML = data;
                this.attachBookingHandlers();
            })
            .catch(error => {
                console.error('❌ Error fetching PO list:', error);
                // Try with full path as fallback
                fetch(`view/home/po_list.php?${params}`)
                    .then(response => response.text())
                    .then(data => {
                        console.log('✅ Fallback fetch successful');
                        Elements.resultArea.innerHTML = data;
                        this.attachBookingHandlers();
                    })
                    .catch(fallbackError => {
                        console.error('❌ Fallback fetch also failed:', fallbackError);
                        Elements.resultArea.innerHTML = `
                            <div class="po-box" style="color: var(--error-color);">
                                Gagal memuat daftar bus. Periksa koneksi atau coba lagi.
                                <br><small>Error: ${error.message}</small>
                            </div>
                        `;
                    });
            });
    },

    attachBookingHandlers() {
        // Attach click handlers to booking buttons
        document.querySelectorAll('[data-booking-url]').forEach(element => {
            element.addEventListener('click', (e) => {
                e.preventDefault();
                const bookingUrl = element.getAttribute('data-booking-url');
                Profile.checkCompletion(() => {
                    window.location.href = bookingUrl;
                });
            });
        });
    }
};

// Autocomplete functionality
const Autocomplete = {
    currentFocus: -1,
    activeInput: null,

    init() {
        if (Elements.fromInput && Elements.fromAutocomplete) {
            this.setupInput(Elements.fromInput, Elements.fromAutocomplete, 'from');
        }
        if (Elements.toInput && Elements.toAutocomplete) {
            this.setupInput(Elements.toInput, Elements.toAutocomplete, 'to');
        }

        // Close autocomplete when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.autocomplete-container')) {
                this.closeAll();
            }
        });
    },

    setupInput(inputElement, listElement, type) {
        let currentFocus = -1;
        let fetchTimeout;

        inputElement.addEventListener('input', () => {
            const val = inputElement.value;
            this.closeAll(listElement);
            listElement.innerHTML = '';

            if (!val || val.length < BeBussConfig.autocompleteMinLength) {
                // Trigger live search even with empty input (original behavior)
                Search.debouncedUpdate();
                return false;
            }

            currentFocus = -1;
            clearTimeout(fetchTimeout);
            
            fetchTimeout = setTimeout(() => {
                this.fetchSuggestions(val, listElement, type);
            }, 200);
            
            // Always trigger live search (original behavior)
            Search.debouncedUpdate();
        });

        inputElement.addEventListener('keydown', (e) => {
            this.handleKeydown(e, listElement);
        });

        inputElement.addEventListener('focus', () => {
            this.activeInput = inputElement;
        });
    },

    fetchSuggestions(query, listElement, type) {
        console.log('🔍 Fetching autocomplete for:', query);
        
        // Use the correct API path relative to current page
        const apiUrl = window.location.pathname.includes('/view/home/') 
            ? '../../api/api_saran_kota.php' 
            : '../api/api_saran_kota.php';
            
        fetch(`${apiUrl}?query=${encodeURIComponent(query)}`)
            .then(response => {
                console.log('📡 Autocomplete API response status:', response.status);
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then(suggestions => {
                console.log('📋 Got autocomplete suggestions:', suggestions);
                if (suggestions && Array.isArray(suggestions)) {
                    this.renderSuggestions(suggestions, listElement);
                } else {
                    console.warn('⚠️ Invalid suggestions format:', suggestions);
                    this.renderFallbackSuggestions(query, listElement);
                }
            })
            .catch(error => {
                console.error('❌ Autocomplete error:', error);
                this.renderFallbackSuggestions(query, listElement);
            });
    },

    renderFallbackSuggestions(query, listElement) {
        // TIDAK ADA FALLBACK - jika API gagal, tampilkan pesan error
        console.log('🔄 API failed, showing error message instead of fallback');
        
        listElement.innerHTML = '';
        const errorItem = document.createElement('div');
        errorItem.className = 'autocomplete-item';
        errorItem.style.cssText = 'color: #999; font-style: italic; cursor: default;';
        errorItem.textContent = 'Tidak dapat memuat saran kota';
        listElement.appendChild(errorItem);
    },

    renderSuggestions(suggestions, listElement) {
        listElement.innerHTML = '';
        this.currentFocus = -1;

        suggestions.forEach((suggestion, index) => {
            const item = document.createElement('div');
            // Use original class name
            item.className = 'autocomplete-list-item';
            
            // Add highlighting like original (find query in suggestion and highlight it)
            const query = this.activeInput ? this.activeInput.value : '';
            if (query) {
                const i = suggestion.toLowerCase().indexOf(query.toLowerCase());
                if (i >= 0) {
                    item.innerHTML = suggestion.substring(0, i) +
                                   "<strong>" + suggestion.substring(i, i + query.length) + "</strong>" +
                                   suggestion.substring(i + query.length) +
                                   "<input type='hidden' value='" + suggestion + "'>";
                } else {
                    item.innerHTML = suggestion + "<input type='hidden' value='" + suggestion + "'>";
                }
            } else {
                item.innerHTML = suggestion + "<input type='hidden' value='" + suggestion + "'>";
            }
            
            item.addEventListener('click', () => {
                const hiddenInput = item.querySelector('input[type="hidden"]');
                const value = hiddenInput ? hiddenInput.value : suggestion;
                this.selectSuggestion(value, listElement);
            });
            listElement.appendChild(item);
        });
    },

    selectSuggestion(value, listElement) {
        if (this.activeInput) {
            this.activeInput.value = value;
            this.closeAll();
            Search.debouncedUpdate();
        }
    },

    handleKeydown(e, listElement) {
        const items = listElement.querySelectorAll('.autocomplete-list-item');
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            this.currentFocus = (this.currentFocus + 1) % items.length;
            this.setActive(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            this.currentFocus = this.currentFocus <= 0 ? items.length - 1 : this.currentFocus - 1;
            this.setActive(items);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (this.currentFocus >= 0 && items[this.currentFocus]) {
                items[this.currentFocus].click();
            }
        } else if (e.key === 'Escape') {
            this.closeAll();
        }
    },

    setActive(items) {
        this.removeActive(items);
        if (this.currentFocus >= 0 && items[this.currentFocus]) {
            items[this.currentFocus].classList.add('active');
        }
    },

    removeActive(items) {
        items.forEach(item => item.classList.remove('active'));
    },

    setActive(items) {
        this.removeActive(items);
        if (items[this.currentFocus]) {
            items[this.currentFocus].classList.add('active');
            items[this.currentFocus].scrollIntoView({ block: 'nearest' });
        }
    },

    removeActive(items) {
        items.forEach(item => item.classList.remove('active'));
    },

    closeAll(except = null) {
        document.querySelectorAll('.autocomplete-list').forEach(list => {
            if (list !== except) {
                list.innerHTML = '';
            }
        });
        this.currentFocus = -1;
    }
};

// Table row navigation
const TableNavigation = {
    init() {
        document.querySelectorAll('.clickable-row').forEach(row => {
            row.addEventListener('click', (e) => {
                // Don't navigate if clicking on a button or link
                if (e.target.closest('button, a')) {
                    return;
                }
                
                const href = row.getAttribute('data-href');
                if (href) {
                    window.location.href = href;
                }
            });
        });
    }
};

// Print functionality
const PrintUtils = {
    init() {
        document.querySelectorAll('.print-button').forEach(button => {
            button.addEventListener('click', () => {
                window.print();
            });
        });
    }
};

// Form enhancements
const FormEnhancements = {
    init() {
        // Add loading state to form submissions
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', (e) => {
                const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
                if (submitBtn) {
                    submitBtn.classList.add('loading');
                    submitBtn.disabled = true;
                }
            });
        });

        // Auto-resize textareas
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', () => {
                textarea.style.height = 'auto';
                textarea.style.height = textarea.scrollHeight + 'px';
            });
        });

        // Enhanced form validation
        this.setupValidation();
    },

    setupValidation() {
        // Real-time validation for email fields
        document.querySelectorAll('input[type="email"]').forEach(input => {
            input.addEventListener('blur', () => {
                const email = input.value.trim();
                if (email && !this.isValidEmail(email)) {
                    input.classList.add('invalid');
                } else {
                    input.classList.remove('invalid');
                }
            });
        });

        // Real-time validation for required fields
        document.querySelectorAll('input[required], select[required], textarea[required]').forEach(input => {
            input.addEventListener('blur', () => {
                if (!input.value.trim()) {
                    input.classList.add('invalid');
                } else {
                    input.classList.remove('invalid');
                }
            });
        });
    },

    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
};

// Enhanced seat selection with better UX
const EnhancedSeatSelection = {
    ...SeatSelection, // Inherit from existing SeatSelection

    init() {
        this.selectedSeats = [];
        this.maxSeats = 4;
        this.pricePerSeat = parseInt(document.querySelector('input[name="harga"]')?.value || 0);

        document.querySelectorAll('.kursi-box').forEach(seat => {
            if (!seat.classList.contains('terisi')) {
                seat.addEventListener('click', () => {
                    this.toggleSeat(seat);
                });

                // Add hover effect for available seats
                seat.addEventListener('mouseenter', () => {
                    if (!seat.classList.contains('selected')) {
                        seat.style.transform = 'scale(1.05)';
                    }
                });

                seat.addEventListener('mouseleave', () => {
                    if (!seat.classList.contains('selected')) {
                        seat.style.transform = '';
                    }
                });
            }
        });

        // Initialize from old selection if available
        this.restoreSelection();

        // Update form on submit
        const form = document.querySelector('form[action="proses_pesan.php"]');
        if (form) {
            form.addEventListener('submit', () => {
                this.updateHiddenInput();
            });
        }
    },

    restoreSelection() {
        // Restore previously selected seats
        document.querySelectorAll('.kursi-box.selected').forEach(seat => {
            const seatNumber = seat.getAttribute('data-kursi');
            if (seatNumber && !this.selectedSeats.includes(seatNumber)) {
                this.selectedSeats.push(seatNumber);
            }
        });
        this.updateSummary();
    },

    toggleSeat(seatElement) {
        const seatNumber = seatElement.getAttribute('data-kursi');
        
        if (seatElement.classList.contains('selected')) {
            // Deselect seat
            seatElement.classList.remove('selected');
            this.selectedSeats = this.selectedSeats.filter(seat => seat !== seatNumber);
            seatElement.style.transform = '';
        } else {
            // Select seat (check limit)
            if (this.selectedSeats.length >= this.maxSeats) {
                this.showSeatLimitWarning();
                return;
            }
            seatElement.classList.add('selected');
            this.selectedSeats.push(seatNumber);
            seatElement.style.transform = 'scale(1.1)';
        }

        this.updateSummary();
    },

    showSeatLimitWarning() {
        // Create a nicer warning than alert
        const warning = document.createElement('div');
        warning.className = 'seat-warning';
        warning.textContent = `Maksimal ${this.maxSeats} kursi per pemesanan.`;
        warning.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--error-color);
            color: white;
            padding: 15px 20px;
            border-radius: var(--border-radius);
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
        `;

        document.body.appendChild(warning);

        setTimeout(() => {
            warning.remove();
        }, 3000);
    },

    updateSummary() {
        const summaryElement = document.getElementById('seat-summary');
        const totalElement = document.getElementById('total-price');

        if (summaryElement) {
            summaryElement.textContent = this.selectedSeats.length > 0 
                ? this.selectedSeats.join(', ')
                : 'Belum ada kursi dipilih';
        }

        if (totalElement) {
            const total = this.selectedSeats.length * this.pricePerSeat;
            totalElement.textContent = Utils.formatCurrency(total);
        }

        // Enable/disable submit button based on selection
        const submitBtn = document.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = this.selectedSeats.length === 0;
        }
    }
};

// Global functions for backward compatibility
window.checkProfileBeforeBooking = function(bookingUrl) {
    Profile.checkCompletion(() => {
        window.location.href = bookingUrl;
    });
};

window.closeModal = function() {
    Modal.hide('profileModal');
};

window.showProfileAlert = function() {
    Profile.showIncompleteAlert();
};

// Initialize application
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 BeBuss DOMContentLoaded triggered');
    
    // Set profile completion status from PHP
    if (typeof window.profileComplete !== 'undefined') {
        BeBussConfig.profileComplete = window.profileComplete;
        console.log('✅ Profile complete status:', window.profileComplete);
    }

    // Debug: Check if key elements exist
    console.log('🔍 Checking key elements:');
    console.log('- result-area:', document.getElementById('result-area'));
    console.log('- from input:', document.getElementById('from'));
    console.log('- to input:', document.getElementById('to'));
    console.log('- date input:', document.getElementById('date'));
    console.log('- from-autocomplete-list:', document.getElementById('from-autocomplete-list'));
    console.log('- to-autocomplete-list:', document.getElementById('to-autocomplete-list'));

    // Initialize modules
    Modal.init();
    console.log('✅ Modal initialized');
    
    Search.init();
    console.log('✅ Search initialized');
    
    Autocomplete.init();
    console.log('✅ Autocomplete initialized');
    
    EnhancedSeatSelection.init();
    TableNavigation.init();
    PrintUtils.init();
    FormEnhancements.init();

    console.log('🎉 BeBuss application initialized with enhancements');
});

// Add CSS for form validation
if (!document.querySelector('#form-validation-styles')) {
    const validationStyles = document.createElement('style');
    validationStyles.id = 'form-validation-styles';
    validationStyles.textContent = `
        .invalid {
            border-color: var(--error-color) !important;
            box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.25) !important;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .seat-warning {
            animation: slideIn 0.3s ease-out;
        }
    `;
    document.head.appendChild(validationStyles);
}
