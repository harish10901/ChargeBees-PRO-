// ChargeBees Universal Form Handler
// Use this on all pages with contact/inquiry forms

function initializeFormHandler(formId, formType = 'general') {
    const form = document.getElementById(formId);
    if (!form) return;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const successMsg = form.querySelector('[data-success-msg]');
        
        if (!submitBtn) return;
        
        const first_name = form.querySelector('input[name="first_name"]')?.value.trim();
        const email = form.querySelector('input[name="email"]')?.value.trim();
        const message = form.querySelector('textarea[name="message"]')?.value.trim();
        
        if (!first_name) { alert('Please enter your name'); return; }
        if (!email) { alert('Please enter your email address'); return; }
        if (!message) { alert('Please enter your message - this field is required'); return; }
        
        const formData = new FormData(this);
        if (!formData.has('form_type')) formData.append('form_type', formType);
        
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i data-lucide="loader" style="width:18px;height:18px;animation:spin 1s linear infinite;"></i> Sending...';
        if (window.lucide) lucide.createIcons();
        
        try {
            const response = await fetch('contact.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (response.ok) {
                if (successMsg) successMsg.style.display = 'block';
                this.reset();
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                if (window.lucide) lucide.createIcons();
                if (successMsg) setTimeout(() => { successMsg.style.display = 'none'; }, 5000);
            } else {
                alert(data.error || 'Failed to send message. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                if (window.lucide) lucide.createIcons();
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to send message. Please check your connection and try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            if (window.lucide) lucide.createIcons();
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    initializeFormHandler('contactForm',     'contact');
    initializeFormHandler('solarForm',       'solar');
    initializeFormHandler('residentialForm', 'residential');
    initializeFormHandler('commercialForm',  'commercial');
    initializeFormHandler('industrialForm',  'industrial');
    initializeFormHandler('groundForm',      'ground');
    initializeFormHandler('partnerForm',     'partner');
    initializeFormHandler('enquiryForm',     'general');
});