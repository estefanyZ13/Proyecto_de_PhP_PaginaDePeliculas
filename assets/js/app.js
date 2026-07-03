/**
 * Interacciones Javascript para la Plataforma de Streaming - Proyecto Final
 */

document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initProfileDropdown();
    initLiveSearch();
    initCarousels();
    initVideoPlayer();
});

/**
 * Inicializa y maneja la alternancia de temas (oscuro/claro)
 */
function initTheme() {
    const themeToggleBtn = document.getElementById('theme-toggle-btn');
    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            const isLight = document.body.classList.toggle('theme-light');
            const themeVal = isLight ? 'light' : 'dark';
            
            // Guardar preferencia en cookie por 30 días
            document.cookie = `theme=${themeVal}; path=${window.BASE_URL || '/'}; max-age=${86400 * 30}; SameSite=Lax`;
            
            // Actualizar texto del botón si existe
            const textNode = themeToggleBtn.querySelector('.theme-text');
            if (textNode) {
                textNode.textContent = isLight ? 'Tema Oscuro' : 'Tema Claro';
            }
        });
    }
}

/**
 * Controla el menú desplegable del perfil en la barra de navegación
 */
function initProfileDropdown() {
    const avatarBtn = document.getElementById('profile-avatar-btn');
    const dropdownMenu = document.getElementById('profile-dropdown-menu');
    
    if (avatarBtn && dropdownMenu) {
        avatarBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
        });
        
        document.addEventListener('click', () => {
            dropdownMenu.classList.remove('show');
        });
    }
}

/**
 * Implementa la barra de búsqueda en tiempo real
 */
function initLiveSearch() {
    const searchInput = document.getElementById('nav-search-input');
    const dropdown = document.getElementById('search-results-dropdown');
    
    if (searchInput && dropdown) {
        searchInput.addEventListener('input', async (e) => {
            const query = e.target.value.trim();
            if (query.length < 2) {
                dropdown.innerHTML = '';
                dropdown.classList.remove('active');
                return;
            }
            
            try {
                const response = await fetch(`${window.BASE_URL}services/json/api.php?action=search&q=${encodeURIComponent(query)}`);
                if (!response.ok) throw new Error('Network error');
                
                const results = await response.json();
                
                if (results.length === 0) {
                    dropdown.innerHTML = '<div style="padding: 15px; text-align: center; font-size: 13px; color: var(--text-muted);">Sin resultados</div>';
                } else {
                    dropdown.innerHTML = results.map(item => `
                        <a href="${window.BASE_URL}views/usuario/detail.php?id=${item.id}&tipo=${item.tipo}" class="search-item">
                            <img src="${window.BASE_URL}${item.imagen_url}" alt="${item.titulo}" onerror="this.src='${window.BASE_URL}assets/img/placeholder.jpg'">
                            <div class="search-item-info">
                                <h4>${item.titulo}</h4>
                                <span>${item.tipo === 'movie' ? 'Película' : 'Serie'} • ${item.año}</span>
                            </div>
                        </a>
                    `).join('');
                }
                dropdown.classList.add('active');
            } catch (err) {
                console.error('Error al realizar búsqueda:', err);
            }
        });
        
        // Cerrar dropdown si se hace click fuera
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });
    }
}

/**
 * Controla el scroll y navegación de carousels horizontales
 */
function initCarousels() {
    // Podemos añadir botones flotantes adelante/atrás para los carruseles si se desea
    const carousels = document.querySelectorAll('.carousel-inner');
    carousels.forEach(carousel => {
        // Habilitar arrastre con el mouse de forma simple
        let isDown = false;
        let startX;
        let scrollLeft;
        
        carousel.addEventListener('mousedown', (e) => {
            isDown = true;
            startX = e.pageX - carousel.offsetLeft;
            scrollLeft = carousel.scrollLeft;
        });
        
        carousel.addEventListener('mouseleave', () => {
            isDown = false;
        });
        
        carousel.addEventListener('mouseup', () => {
            isDown = false;
        });
        
        carousel.addEventListener('mousemove', (e) => {
            if(!isDown) return;
            e.preventDefault();
            const x = e.pageX - carousel.offsetLeft;
            const walk = (x - startX) * 2; // velocidad de scroll
            carousel.scrollLeft = scrollLeft - walk;
        });
    });
}

/**
 * Controla el reproductor de vídeo simulado (Modal)
 */
function initVideoPlayer() {
    const playBtn = document.getElementById('play-video-btn');
    const modal = document.getElementById('video-player-modal');
    const closeBtn = document.getElementById('modal-close-btn');
    const iframe = document.getElementById('modal-video-iframe');
    
    let progressInterval;
    let simulatedProgress = 0;
    
    if (playBtn && modal && closeBtn && iframe) {
        const videoUrl = playBtn.dataset.videoUrl;
        const mediaId = playBtn.dataset.mediaId;
        const mediaTipo = playBtn.dataset.mediaTipo;
        
        playBtn.addEventListener('click', () => {
            // Cargar la URL del video en el iframe
            iframe.src = videoUrl;
            modal.classList.add('active');
            
            // Iniciar simulación de guardado de progreso en el historial
            simulatedProgress = 0;
            progressInterval = setInterval(async () => {
                simulatedProgress += 5; // Aumentar de 5 en 5% cada 3 segundos
                if (simulatedProgress > 100) {
                    simulatedProgress = 100;
                    clearInterval(progressInterval);
                }
                
                // Enviar actualización del historial por AJAX
                try {
                    const postData = {
                        progreso: simulatedProgress
                    };
                    if (mediaTipo === 'movie') {
                        postData.movie_id = mediaId;
                    } else {
                        postData.series_id = mediaId;
                    }
                    
                    await fetch(`${window.BASE_URL}services/json/api.php?action=update_history`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(postData)
                    });
                } catch(e) {
                    console.error('Error al actualizar historial:', e);
                }
            }, 3000);
        });
        
        const closeModal = () => {
            iframe.src = '';
            modal.classList.remove('active');
            if (progressInterval) {
                clearInterval(progressInterval);
            }
            // Recargar para que aparezca en el "Continuar Viendo"
            if (simulatedProgress > 0) {
                window.location.reload();
            }
        };
        
        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
    }
}

/**
 * Alterna el estado de favoritos de un contenido mediante AJAX
 */
async function toggleFavorite(btn, mediaId, tipo) {
    try {
        const postData = {};
        if (tipo === 'movie') {
            postData.movie_id = mediaId;
        } else {
            postData.series_id = mediaId;
        }
        
        const response = await fetch(`${window.BASE_URL}services/json/api.php?action=toggle_favorite`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(postData)
        });
        
        if (!response.ok) throw new Error('Unauthenticated or bad request');
        
        const res = await response.json();
        
        if (res.success) {
            const icon = btn.querySelector('.btn-icon');
            const text = btn.querySelector('.btn-text');
            
            if (res.status === 'added') {
                if (icon) icon.textContent = '✓';
                if (text) text.textContent = 'En Favoritos';
                btn.style.backgroundColor = 'var(--success)';
            } else {
                if (icon) icon.textContent = '+';
                if (text) text.textContent = 'Mi Lista';
                btn.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
            }
        }
    } catch (e) {
        console.error('Error al guardar favorito:', e);
        alert('Por favor inicie sesión para agregar favoritos.');
        window.location.href = `${window.BASE_URL}views/auth/login.php`;
    }
}
