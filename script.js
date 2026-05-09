document.getElementById('ai-search-trigger').addEventListener('click', function() {
    let fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = 'image/*';
    fileInput.capture = 'camera'; 
    
    fileInput.onchange = e => { 
        let file = e.target.files[0];
        console.log("Image selected for AI Search:", file.name);
       
    }
    
    fileInput.click();
});
function toggleLanguage() {
    let langText = document.getElementById('lang-text');
    
    if (langText.innerText === 'EN') {
        langText.innerText = 'AR';
        document.body.style.direction = 'rtl'; 
        console.log("Language set to Arabic");
    } else {
        langText.innerText = 'EN';
        document.body.style.direction = 'ltr'; 
        console.log("Language set to English");
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