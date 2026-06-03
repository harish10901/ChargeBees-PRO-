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
        
        // Validate required fields
        const first_name = form.querySelector('input[name="first_name"]')?.value.trim();
        const email = form.querySelector('input[name="email"]')?.value.trim();
        const message = form.querySelector('textarea[name="message"]')?.value.trim();
        
        if (!first_name) {
            alert('Please enter your name');
            return;
        }
        
        if (!email) {
            alert('Please enter your email address');
            return;
        }
        
        if (!message) {
            alert('Please enter your message - this field is required');
            return;
        }
        
        const formData = new FormData(this);
        
        // Add form type if not already present
        if (!formData.has('form_type')) {
            formData.append('form_type', formType);
        }
        
        // Debug: Log the form data being sent
        console.log('Submitting form:', formType);
        console.log('Form data entries:', Array.from(formData.entries()));
        
        // Show loading state
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
                // Show success message
                if (successMsg) {
                    successMsg.style.display = 'block';
                }
                
                // Reset form
                this.reset();
                
                // Reset button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                if (window.lucide) lucide.createIcons();
                
                // Hide success message after 5 seconds
                if (successMsg) {
                    setTimeout(() => {
                        successMsg.style.display = 'none';
                    }, 5000);
                }
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

// Initialize all forms on page load
document.addEventListener('DOMContentLoaded', function() {
    // Contact form
    initializeFormHandler('contactForm', 'contact');
    
    // Solar inquiry form
    initializeFormHandler('solarForm', 'solar');
    
    // Residential form
    initializeFormHandler('residentialForm', 'residential');
    
    // Commercial form
    initializeFormHandler('commercialForm', 'commercial');
    
    // Industrial form
    initializeFormHandler('industrialForm', 'industrial');
    
    // Ground mounted form
    initializeFormHandler('groundForm', 'ground');
    
    // Partner form
    initializeFormHandler('partnerForm', 'partner');
    
    // Generic form
    initializeFormHandler('enquiryForm', 'general');
});