// About page — navbar toggle
const navToggle = document.getElementById('navToggle');
const navMenu   = document.getElementById('navMenu');
if (navToggle) {
    navToggle.addEventListener('click', () => {
        navToggle.classList.toggle('active');
        navMenu.classList.toggle('active');
    });
}
