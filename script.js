document.getElementById('ai-search-trigger').addEventListener('click', function() {
    // Create a hidden input to trigger the camera/file gallery
    let fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = 'image/*';
    fileInput.capture = 'camera'; // Optional: suggests opening camera on mobile
    
    fileInput.onchange = e => { 
        let file = e.target.files[0];
        console.log("Image selected for AI Search:", file.name);
        // Here you would use AJAX to send the file to your PHP/AI backend
    }
    
    fileInput.click();
});
function toggleLanguage() {
    let langText = document.getElementById('lang-text');
    
    if (langText.innerText === 'EN') {
        langText.innerText = 'AR';
        document.body.style.direction = 'rtl'; // Switches site to Right-to-Left for Arabic
        console.log("Language set to Arabic");
        // Add your logic here to reload with ?lang=ar
    } else {
        langText.innerText = 'EN';
        document.body.style.direction = 'ltr'; // Switches site back to Left-to-Right
        console.log("Language set to English");
        // Add your logic here to reload with ?lang=en
    }
}
function toggleSubmenu() {
    let submenu = document.getElementById('product-submenu');
    if (submenu.style.display === "none") {
        submenu.style.display = "block";
    } else {
        submenu.style.display = "none";
    }
}