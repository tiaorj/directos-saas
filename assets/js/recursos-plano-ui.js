document.addEventListener("DOMContentLoaded", function () {
    const recursos = window.DirectOSPlanoRecursos || null;

    if (!recursos) {
        return;
    }

    function esconderElemento(el) {
        if (!el) {
            return;
        }

        const itemMenu = el.closest("li");
        const botaoGrupo = el.closest(".btn-group");

        if (itemMenu && el.classList.contains("dropdown-item")) {
            itemMenu.classList.add("d-none");
            return;
        }

        if (botaoGrupo && botaoGrupo.children.length <= 1) {
            botaoGrupo.classList.add("d-none");
            return;
        }

        el.classList.add("d-none");
    }

    function esconderLinksPorHref(partesHref) {
        document.querySelectorAll("a[href]").forEach(function (link) {
            const href = link.getAttribute("href") || "";

            const encontrou = partesHref.some(function (parte) {
                return href.indexOf(parte) !== -1;
            });

            if (encontrou) {
                esconderElemento(link);
            }
        });
    }

    function esconderBotoesPorTexto(textos) {
        document.querySelectorAll("button, a").forEach(function (el) {
            const texto = (el.textContent || "").trim().toLowerCase();

            const encontrou = textos.some(function (parte) {
                return texto.indexOf(parte) !== -1;
            });

            if (encontrou) {
                esconderElemento(el);
            }
        });
    }

    function ocultarBlocosIA() {
        document.querySelectorAll("[data-ia-os], [data-ia-servico]").forEach(esconderElemento);
        esconderBotoesPorTexto(["melhorar descrição", "gerar checklist", "sugerir prioridade", "assistente ia"]);
    }

    if (recursos.anexos === false) {
        esconderLinksPorHref([
            "anexar.php",
            "abrir_anexo.php",
            "alternar_visibilidade_anexo.php",
            "excluir_anexo.php"
        ]);
        esconderBotoesPorTexto(["anexar", "novo anexo", "abrir anexo"]);
    }

    if (recursos.whatsapp === false) {
        esconderLinksPorHref([
            "nova_mensagem_whatsapp.php",
            "enviar_whatsapp_n8n.php",
            "wa.me"
        ]);
        esconderBotoesPorTexto(["whatsapp", "abrir whatsapp", "salvar e abrir whatsapp"]);

        document.querySelectorAll('input[name="PrepararWhatsAppAposSalvar"], input[name="PrepararWhatsAppAposAtualizar"]').forEach(function (campo) {
            const bloco = campo.closest(".card") || campo.closest(".form-check") || campo;
            esconderElemento(bloco);
        });
    }

    if (recursos.areaCliente === false) {
        esconderLinksPorHref([
            "public/os.php",
            "/public/os.php"
        ]);
        esconderBotoesPorTexto(["área do cliente", "area do cliente", "link público", "link publico", "acompanhamento"]);
    }

    if (recursos.ia === false) {
        ocultarBlocosIA();
    }
});
