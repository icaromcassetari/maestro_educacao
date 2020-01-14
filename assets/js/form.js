jQuery(document).ready(function () {
    /* Turmas */
    jQuery("h3.my_toggle").append("<span style='position: relative; left: 10px; font-weight: 800;'>+</span>");
    let turmas = {
        'quantidade': jQuery("#turmas").children().length,
        'add': function () {
            this.quantidade = this.quantidade + 1
        },
        'remove': function () {
            this.quantidade = this.quantidade - 1
        },
        'getQuantidade': function () { return this.quantidade; }
    }


    jQuery("#addTurma").click(function () {
        turmas.add();
        jQuery("#turmas").append(`<div class="turma" style="text-align: left;">
        <label for="nome${turmas.getQuantidade()}" style="text-align: left;">Nome da ${turmas.getQuantidade()}ª Turma</label>
        <input style="text-align: left;" required id="nome${turmas.getQuantidade()}" name='nome[${turmas.getQuantidade()}]' placeholder="Turma ${turmas.getQuantidade()} - A"\><br>
        </div>`
        );
    });

    jQuery("#removeTurma").click(function () {
        if (turmas.getQuantidade() > 1) {
            turmas.remove();
            jQuery("#turmas .turma").last().remove();
        }
    });

    // Mostra turmas
    jQuery("#turma_buttons button").click(function () {
        id = jQuery(this).data("id");
        nome = jQuery(this).text();
        jQuery("#turma_table tbody tr").fadeOut();
        jQuery(`#turma_table tbody tr.turma_${id}`).fadeIn();
        jQuery("form.excluir_turma, form.editar_turma").removeClass("d-none");
        jQuery("form.excluir_turma input[name=turma_id], form.editar_turma input[name=turma_id]").val(id);

        jQuery("form.editar_turma input[name=nome]").val(nome);

    });

    // Exluir turmas
    jQuery("#btnExcluirTurma").click(function (event) {
        event.preventDefault();
        if (confirm("Deseja remover esta turma e TODOS SEU ESTUDANTES PERMANENTEMENTE??")) {

            jQuery(".excluir_turma").submit();

            //jQuery(".loading-tonzera").show();
        }
    });

    // Collapse    
    jQuery("h3.my_toggle").click(function (event) {
        console.log(jQuery(this).parent().find("div.my_toggle,form.my_toggle").css('display'));
        if (jQuery(this).parent().find("div.my_toggle,form.my_toggle").css('display') === 'none') {
            jQuery("div.my_toggle,form.my_toggle").hide();
            jQuery("h3.my_toggle").find("span").text("+");
            jQuery(this).parent().find("div.my_toggle,form.my_toggle").css('display', 'block');
            jQuery(this).find("span").text("-");
        }
        else {
            jQuery(this).find("span").text("+");
            jQuery(this).parent().find("div.my_toggle,form.my_toggle").css('display', 'none');
        }
        //jQuery("div.my_toggle, form.my_toggle").hide();
    });


    // Modal
    // Get the modal
    var modal = jQuery('#myModal');
    // Abre modal
    jQuery("#btnModal, .classBtnModal").click(function () {        
        id = jQuery(this).data("id");
        jQuery("#idModalProduto").val(id);
        modal.fadeIn(500);
    });

    // Botao de fechar modal
    jQuery("#closeModal").click(function () {
        modal.fadeOut(500);
    });

    // Quando clica fora do model
    jQuery(window).click(function (event) {

        if (event.target == jQuery(modal).get(0)) {
            modal.fadeOut(500);
        }
    });

    jQuery("form").submit(function () {
        jQuery(".loading-tonzera").show();
    });
    jQuery(".excluir_aluno").click(function (event) {
        event.preventDefault();
        console.log(this.form);
        if (confirm("Deseja remover este PERMANENTEMENTE?")) {
            this.form.submit();
            jQuery(".loading-tonzera").show();
        }
    });

    jQuery(".editar_aluno").click(function (event) {
        jQuery("span.nome_aluno").show();
        jQuery("form.form_editar_aluno").hide();

        jQuery(this).parent().parent().find("span.nome_aluno").hide();
        jQuery(this).parent().parent().find("form.form_editar_aluno").show();
    });

    jQuery(".cancelar_form_aluno").click(function (event) {
        jQuery("span.nome_aluno").show();
        jQuery("form.form_editar_aluno").hide();
    });

});

