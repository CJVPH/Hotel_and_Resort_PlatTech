/**
 * Payment Pages - Common JavaScript
 * Paradise Hotel & Resort
 */

// Credit Card Formatting and Validation
if (document.getElementById('card_number')) {
    // Format card number with spaces
    document.getElementById('card_number').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\s/g, '');
        let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
        e.target.value = formattedValue;
    });
}

if (document.getElementById('expiry_date')) {
    // Format expiry date
    document.getElementById('expiry_date').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length >= 2) {
            value = value.slice(0, 2) + '/' + value.slice(2, 4);
        }
        e.target.value = value;
    });
}

if (document.getElementById('cvv')) {
    // CVV numbers only
    document.getElementById('cvv').addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/\D/g, '');
    });
}

// Credit Card Form Validation
if (document.getElementById('creditCardForm')) {
    document.getElementById('creditCardForm').addEventListener('submit', function(e) {
        const cardNumber = document.getElementById('card_number').value.replace(/\s/g, '');
        const expiryDate = document.getElementById('expiry_date').value;
        const cvv = document.getElementById('cvv').value;

        // Validate card number (Luhn algorithm)
        if (!isValidCardNumber(cardNumber)) {
            e.preventDefault();
            alert('Please enter a valid card number');
            return false;
        }

        // Validate expiry date
        if (!isValidExpiryDate(expiryDate)) {
            e.preventDefault();
            alert('Please enter a valid expiry date (MM/YY)');
            return false;
        }

        // Validate CVV
        if (cvv.length < 3 || cvv.length > 4) {
            e.preventDefault();
            alert('Please enter a valid CVV');
            return false;
        }

        // Disable submit button
        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        }
    });
}

/**
 * Luhn Algorithm for Credit Card Validation
 * @param {string} cardNumber - Card number without spaces
 * @returns {boolean} - True if valid
 */
function isValidCardNumber(cardNumber) {
    let sum = 0;
    let isEven = false;
    
    for (let i = cardNumber.length - 1; i >= 0; i--) {
        let digit = parseInt(cardNumber[i]);
        
        if (isEven) {
            digit *= 2;
            if (digit > 9) {
                digit -= 9;
            }
        }
        
        sum += digit;
        isEven = !isEven;
    }
    
    return sum % 10 === 0;
}

/**
 * Validate Expiry Date
 * @param {string} expiryDate - Expiry date in MM/YY format
 * @returns {boolean} - True if valid and not expired
 */
function isValidExpiryDate(expiryDate) {
    const parts = expiryDate.split('/');
    if (parts.length !== 2) return false;
    
    const month = parseInt(parts[0]);
    const year = parseInt('20' + parts[1]);
    
    if (month < 1 || month > 12) return false;
    
    const now = new Date();
    const expiry = new Date(year, month - 1);
    
    return expiry >= now;
}

// Form Submit Loading State
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
        const submitBtn = this.querySelector('.btn-submit');
        if (submitBtn && !submitBtn.disabled) {
            submitBtn.disabled = true;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            // Re-enable after 10 seconds as fallback
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }, 10000);
        }
    });
});

// File Input Preview
document.querySelectorAll('input[type="file"]').forEach(input => {
    input.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const fileSize = (file.size / 1024 / 1024).toFixed(2); // MB
            if (fileSize > 5) {
                alert('File size must be less than 5MB');
                this.value = '';
                return;
            }
            
            // Show file name
            const label = this.previousElementSibling;
            if (label && label.tagName === 'LABEL') {
                const fileName = file.name;
                if (fileName.length > 30) {
                    label.title = fileName;
                }
            }
        }
    });
});

// Copy to Clipboard Function
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showNotification('Copied to clipboard!');
        }).catch(err => {
            console.error('Failed to copy:', err);
        });
    } else {
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            showNotification('Copied to clipboard!');
        } catch (err) {
            console.error('Failed to copy:', err);
        }
        document.body.removeChild(textarea);
    }
}

// Show Notification
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#28a745' : '#dc3545'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        animation: slideIn 0.3s ease-out;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Add click-to-copy for reference numbers
document.querySelectorAll('.reference-number, .ref-value').forEach(element => {
    element.style.cursor = 'pointer';
    element.title = 'Click to copy';
    element.addEventListener('click', function() {
        copyToClipboard(this.textContent.trim());
    });
});

// Add animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
