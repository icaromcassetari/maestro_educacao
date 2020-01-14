<?php


function _get_filhos()
{
    global $wpdb;
    $filhos = $wpdb->get_results("SELECT wp_alunos.*,wp_users.display_name FROM wp_alunos join wp_users on wp_users.id = wp_alunos.escola_id where pais_id = " . um_profile_id() . ";");
    $retorno = "";
    if (count($filhos) == 0) {
        echo "<p style='color: red;'><b>Ainda não possui filhos vinculados à você</b></p>";
    }
    return $filhos;
}

function _get_ver_filho()
{
    global $wpdb;
    $filhos = $wpdb->get_results("SELECT wp_alunos.*,wp_users.display_name FROM wp_alunos join wp_users on wp_users.id = wp_alunos.escola_id where pais_id = " . um_profile_id() . ";");
    $retorno = "";
    ?>
    <p style='margin-top: 15px;'><?php
    if (count($filhos) > 0) {
        foreach ($filhos as $filho) {
            echo "<b>Nome: </b>{$filho->nome} - <b>Escola: </b>{$filho->display_name}</br>";
        }
    }
    return $filhos;
    ?></p><?php
}

function vincula_aluno()
{
    global $wpdb;

    $user_id = um_profile_id();
    $escola_id = $_POST['escola_id'];
    $codigo = $_POST['codigo'];
    if (!session_id()) {
        session_start();
    }

    if (_get_role() == "um_pais") {
        $aluno = $wpdb->get_results(
            "SELECT pais_id FROM wp_alunos 
            where escola_id = {$escola_id} and codigo = '{$codigo}';"
        )[0];

        if ($aluno->pais_id == null) {
            if ($wpdb->update('wp_alunos', ['pais_id' => $user_id], ['escola_id' => $escola_id, 'codigo' => $codigo]))
                $_SESSION['mensagens'][] = array('type' => 'success', 'message' => 'Seu filho foi vinculado a voce. <br> Você agora pode ver os produtos que ele precisa');
            else $_SESSION['mensagens'][] = array('type' => 'error', 'message' => 'Parece que um dos cógidos estão incorretos, por favor verifique-os');
        } elseif (count($aluno->pais_id) == 1) {
            $_SESSION['mensagens'][] = array('type' => 'error', 'message' => 'Esse aluno já possui um pai vinculado à ele, converse com a administracao para verificar');
        } else {
            echo "else";
        }
        wp_redirect('https://www.maestroeducacao.com.br/area-dos-pais/');
        //exit();                                 

    }
}

//add_shortcode( 'form_turma_submit','post_turma');

add_action("admin_post_vincula_aluno", 'vincula_aluno');

function render_form_pais_declare_aluno()
{
    global $wp;
    //$url = home_url( add_query_arg( array(), $wp->request ) )."/";
    // TODO: Esconder num dropdown quando tiver um ou mais filhos
    $url = esc_url(admin_url('admin-post.php'));
    if (_get_role() == "um_pais") {
        $filhos = _get_filhos();
        $shouldToggle = count($filhos) > 0;
        ?>
        <?php if ($shouldToggle) : ?>
            <div style="text-align: left;">
            <h3 class='my_toggle' align='left'>Vincule outro filho a você</h3>
        <?php endif; ?>
        <form action='<?php echo $url ?>' method='POST' <?php if ($shouldToggle) {
            echo "class='vc_col-12 my_toggle'";
        } else {
            echo "class='vc_col-12'";
        } ?> <?php if ($shouldToggle) {
            echo "style='display: none; text-align: left;'";
        } else {
            echo "style='text-align: left;'";
        } ?> enctype="multipart/form-data">
            <?php if (!$shouldToggle) : ?> <h3>Vincule seu filho à você</h3> <?php endif; ?>
            <p>Digite o código do seu filho para fazer o vínculo</p>
            <label for="cod_escola">Código da escola</label>
            <p style='text-align: left;'><input id="cod_escola" name="escola_id"/></p>
            <br>
            <label for="cod_aluno">Código do Aluno</label>
            <p style='text-align:left;'><input id="cod_aluno" name="codigo"/></p>

            <p style='text-align: left; display: none;'><input type='hidden' name='action' value='vincula_aluno' \></p>

            <p style='text-align: left;'><input name='submit' type='submit' value='Criar vinculo' \></p>
        </form>
        <?php if ($shouldToggle) : ?>
            </div>
        <?php endif; ?>

        <?php
    }
    _get_ver_filho();
}

add_shortcode('form_pais_vincula_aluno', 'render_form_pais_declare_aluno');
