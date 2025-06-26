document.addEventListener('DOMContentLoaded', function() {
    const carousel = document.querySelector('.carousel');
    if (!carousel) return;

    const track = carousel.querySelector('.carousel-track');
    const slides = carousel.querySelectorAll('.carousel-slide');
    const prevBtn = carousel.querySelector('.prev');
    const nextBtn = carousel.querySelector('.next');
    
    let currentIndex = 0;
    const slideCount = slides.length;
    let intervalId;
    const slideInterval = 5000;
    
    function showSlide(index) {
        // Скрываем все слайды
        slides.forEach(slide => {
            slide.classList.remove('active');
        });
        
        // Показываем текущий слайд
        slides[index].classList.add('active');
        
        // Обновляем индекс
        currentIndex = index;
    }
    
    function nextSlide() {
        const newIndex = (currentIndex + 1) % slideCount;
        showSlide(newIndex);
    }
    
    function prevSlide() {
        const newIndex = (currentIndex - 1 + slideCount) % slideCount;
        showSlide(newIndex);
    }
    
    function startAutoSlide() {
        intervalId = setInterval(nextSlide, slideInterval);
    }
    
    function stopAutoSlide() {
        clearInterval(intervalId);
    }
    
    // Инициализация
    function initCarousel() {
        // Показываем первый слайд
        showSlide(0);
        
        // Запускаем автопрокрутку
        startAutoSlide();
        
        // Навешиваем обработчики
        nextBtn.addEventListener('click', () => {
            stopAutoSlide();
            nextSlide();
            startAutoSlide();
        });
        
        prevBtn.addEventListener('click', () => {
            stopAutoSlide();
            prevSlide();
            startAutoSlide();
        });
        
        // Пауза при наведении
        carousel.addEventListener('mouseenter', stopAutoSlide);
        carousel.addEventListener('mouseleave', startAutoSlide);
    }
    
    // Ждем загрузки всех изображений
    const images = carousel.querySelectorAll('img');
    let loadedImages = 0;
    
    if (images.length === 0) {
        initCarousel();
    } else {
        images.forEach(img => {
            if (img.complete) {
                imageLoaded();
            } else {
                img.addEventListener('load', imageLoaded);
            }
        });
    }
    
    function imageLoaded() {
        loadedImages++;
        if (loadedImages === images.length) {
            initCarousel();
        }
    }

    // Анимация секции миссии при скролле
    const animateOnScroll = () => {
        const missionSection = document.querySelector('.mission-section');
        if (missionSection) {
            const missionPosition = missionSection.getBoundingClientRect().top;
            const screenPosition = window.innerHeight / 1.2;
            
            if (missionPosition < screenPosition) {
                missionSection.style.opacity = '1';
                missionSection.style.transform = 'translateY(0)';
            }
        }
    };
    
    // Инициализация анимации
    const missionSection = document.querySelector('.mission-section');
    if (missionSection) {
        missionSection.style.opacity = '0';
        missionSection.style.transform = 'translateY(30px)';
        missionSection.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    }
    
    window.addEventListener('scroll', animateOnScroll);
    animateOnScroll();
});