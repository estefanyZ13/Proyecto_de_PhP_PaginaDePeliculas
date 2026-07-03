<?php
/**
 * Footer común del proyecto
 */
require_once __DIR__ . '/../../config/config.php';
?>
    <footer class="footer">
        <div class="footer-logo">🍿 Proyecto<span>Final</span></div>
        <ul class="footer-links">
            <li><a href="#">Términos de Uso</a></li>
            <li><a href="#">Declaración de Privacidad</a></li>
            <li><a href="#">Dispositivos Compatibles</a></li>
            <li><a href="#">Centro de Ayuda</a></li>
            <li><a href="#">Contacto</a></li>
        </ul>
        <p class="footer-copy">© <?php echo date('Y'); ?> Proyecto Final - Universidad Tecnológica de Panamá. Todos los derechos reservados. Desarrollado de forma profesional con PHP y MySQL.</p>
    </footer>

    <!-- Scripts Javascript -->
    <script src="<?php echo BASE_URL; ?>assets/js/app.js"></script>
</body>
</html>
