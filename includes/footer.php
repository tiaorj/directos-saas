        </div>
    </main>
</div>

<?php
require_once __DIR__ . "/../config/config.php";

$recursosPlanoInterface = [
    "anexos" => true,
    "areaCliente" => true,
    "whatsapp" => true,
    "ia" => true
];

if (isset($_SESSION["EmpresaId"], $conn) && $conn instanceof PDO) {
    require_once __DIR__ . "/planos.php";

    $empresaIdFooter = (int)$_SESSION["EmpresaId"];
    $recursosPlanoInterface["anexos"] = empresaPodeUsarRecursoPlano($conn, $empresaIdFooter, "anexos")["permitido"];
    $recursosPlanoInterface["areaCliente"] = empresaPodeUsarRecursoPlano($conn, $empresaIdFooter, "area_cliente")["permitido"];
    $recursosPlanoInterface["whatsapp"] = empresaPodeUsarRecursoPlano($conn, $empresaIdFooter, "whatsapp")["permitido"];
    $recursosPlanoInterface["ia"] = empresaPodeUsarRecursoPlano($conn, $empresaIdFooter, "ia")["permitido"];
}
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
window.DirectOSPlanoRecursos = <?= json_encode($recursosPlanoInterface, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= htmlspecialchars(rtrim(APP_URL, "/")) ?>/assets/js/recursos-plano-ui.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const body = document.body;
    const sidebar = document.getElementById("app-sidebar");
    const menuToggle = document.querySelector(".mobile-menu-toggle");
    const sidebarOverlay = document.querySelector(".app-sidebar-overlay");
    const mobileMenuQuery = window.matchMedia("(max-width: 991px)");

    function closeMobileMenu() {
        body.classList.remove("menu-open");

        if (menuToggle) {
            menuToggle.setAttribute("aria-expanded", "false");
            menuToggle.setAttribute("aria-label", "Abrir menu");
        }
    }

    function openMobileMenu() {
        if (!sidebar) {
            return;
        }

        body.classList.add("menu-open");

        if (menuToggle) {
            menuToggle.setAttribute("aria-expanded", "true");
            menuToggle.setAttribute("aria-label", "Fechar menu");
        }
    }

    if (menuToggle) {
        menuToggle.addEventListener("click", function () {
            if (body.classList.contains("menu-open")) {
                closeMobileMenu();
                return;
            }

            openMobileMenu();
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener("click", closeMobileMenu);
    }

    document.querySelectorAll(".app-sidebar a").forEach(function (link) {
        link.addEventListener("click", function () {
            if (mobileMenuQuery.matches) {
                closeMobileMenu();
            }
        });
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            closeMobileMenu();
        }
    });

    function handleMenuBreakpoint(event) {
        if (!event.matches) {
            closeMobileMenu();
        }
    }

    if (mobileMenuQuery.addEventListener) {
        mobileMenuQuery.addEventListener("change", handleMenuBreakpoint);
    } else if (mobileMenuQuery.addListener) {
        mobileMenuQuery.addListener(handleMenuBreakpoint);
    }

    setTimeout(function () {
        const modaisAbertos = document.querySelectorAll(".modal.show");

        if (modaisAbertos.length === 0) {
            document.querySelectorAll(".modal-backdrop").forEach(function (backdrop) {
                backdrop.remove();
            });

            document.body.classList.remove("modal-open");
            document.body.style.removeProperty("overflow");
            document.body.style.removeProperty("padding-right");
        }
    }, 500);
});
</script>
</body>
</html>
