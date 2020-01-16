<?php
function render_pedidos()
{
    global $wpdb;
    $url = esc_url(admin_url('admin-post.php'));
    $pedidos = $wpdb->get_results("SELECT wp_pedidos.id FROM wp_pedidos join wp_itens_pedidos on wp_pedidos.id = wp_itens_pedidos.pedido_id where escola_id = " . um_profile_id() . " group by id;");
    $itens_pedidos;
    if (isset($_GET['pedido'])) {
        $wpdb->show_errors();
        $itens_pedidos = $wpdb->get_results("SELECT wp_alunos.id, wp_alunos.nome,wp_alunos.pais_id, wp_itens_pedidos.pai_comprou, 
        wp_itens_pedidos.nome_produto, wp_turmas.nome as turma, data_limite FROM wp_itens_pedidos 
        join wp_pedidos on wp_pedidos.id = wp_itens_pedidos.pedido_id
        join wp_alunos on wp_itens_pedidos.aluno_id = wp_alunos.id
        join wp_turmas on wp_turmas.id = wp_alunos.turmas_id
        where wp_pedidos.escola_id = " . um_profile_id() . " and pedido_id = " . intval($_GET['pedido']) . " order by id;");
        /*
        foreach($itens_pedidos as $item){
            echo $item->nome." turma: ".$item->turma." nome: ".$item->nome_produto." pai ".$item->pai_comprou;
        }*/
    }
    if (count($pedidos) > 0) {
        ?>

        <div style='text-align: left;'>
            <form method='get' id="form_get_pedidos">
                <select name='pedido' onchange="jQuery('#form_get_pedidos').submit();">
                    <option>Escolha um pedido</option>
                    <?php foreach ($pedidos as $pedido) { ?>
                        <option value="<?php echo $pedido->id; ?>">Pedido <?php echo $pedido->id; ?></option>
                    <?php } ?>
                </select>
            </form>
            <?php if (isset($_GET['pedido'])) { ?>
                <h3>Atualizar data limite</h3>
                <form method='post' class='loading_maestro' action='<?php echo $url; ?>'>
                    <p>Data limite atual: <?php echo $itens_pedidos[0]->data_limite; ?></p>
                    <label for='novaDataLimite'>Nova Data Limite</label>
                    <input id='novaDataLimite' type="date" name='new_data_limite'>
                    <input type="hidden" value='<?php echo $itens_pedidos[0]->data_limite; ?>' name='old_data_limite'>
                    <input type='hidden' value='<?php echo $_GET['pedido']; ?>' name="id_pedido">
                    <input type='hidden' name='action' value='update_data_limite'>
                    <input name='submit' type='submit' value='Atualizar' \>
                </form>
                <table>
                    <thead>
                    <tr>
                        <th>Nome aluno</th>
                        <th>Turma</th>
                        <th>Produto</th>
                        <th>Pai comprou</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($itens_pedidos as $item) { ?>
                        <tr>
                            <td><?php echo $item->nome ?></td>
                            <td><?php echo $item->turma ?></td>
                            <td><?php echo $item->nome_produto ?></td>

                            <?php
                            if (empty($item->pais_id)) {
                                echo "<td style='background-color: #ff2121; color: white;'> Não tem pai vinculado </td>";
                            } else {
                                echo "<td> $item->pai_comprou </td>";
                            }
                            ?>

                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
        </div>

        <?php
    }
}

add_shortcode('table_pedidos', 'render_pedidos');

function update_data_limite()
{
    global $wpdb;

    $data = $_POST['new_data_limite'];
    $old = $_POST['old_data_limite'];
    list($newAno, $newMes, $newDia) = sscanf($data, '%d-%d-%d');
    list($oldAno, $oldMes, $oldDia) = sscanf($old, '%d-%d-%d');
    list($nowAno, $nowMes, $nowDia) = sscanf(date("Y-m-d"), "%d-%d-%d");
    $now = $nowAno . "-" . $nowMes . "-" . $nowDia;
    $new = $newAno . "-" . $newMes . "-" . $newDia;
    $old = $oldAno . "-" . $oldMes . "-" . $oldDia;
    if (!session_id()) {
        session_start();
    }

    if (
        checkdate($newMes, $newDia, $newAno) && (strtotime($new) > strtotime($old)) && (strtotime($new) > strtotime($now)) &&
        _get_role() == "um_escola"
    ) {

        $where = array('id' => $_POST['id_pedido'], "escola_id" => um_profile_id());
        $values = array('data_limite' => $new);
        $wpdb->show_errors();
        if ($wpdb->update("wp_pedidos", $values, $where)) {
            $_SESSION['mensagens'][] = array('type' => 'success', 'message' => 'Data limite atualizada');
            wp_redirect('/user');
        } else {
            $_SESSION['mensagens'][] = array('type' => 'error', 'message' => 'Não foi possível realizar a ação');
            wp_redirect('/user');
        }
    } else {
        $_SESSION['mensagens'][] = array('type' => 'error', 'message' => 'Não foi possível realizar a ação');
        wp_redirect('/user');
    }
}

add_action("admin_post_update_data_limite", 'update_data_limite');


// enviando dados atraves de get para exibir nome da turma na exclusão > nome _GET > hao17sd8sdf98fjj281
function _render_turma_table()
{
    global $wpdb;
    $url = esc_url(admin_url('admin-post.php'));
    if (_get_role() == "um_escola") {
        $turmas = $wpdb->get_results("SELECT id,nome FROM wp_turmas where user_id = " . um_profile_id() . ";");
        $alunos = $wpdb->get_results("SELECT id,nome,codigo,turmas_id FROM wp_alunos where escola_id = " . um_profile_id() . ";");
        $retorno = array();
        echo "<div>";
        echo "<div style='display: true;'>";
        if (count($turmas) > 0) {
            echo "<p>Clique no botão referente à turma para ver os alunos e os códigos de acesso para os pais</p>";
        }
        if (count($turmas) == 0) {
            echo "<p style='color: red;'>Você ainda não tem turmas cadastradas</p>";
        }
        echo "<div id='turma_buttons'>";
        foreach ($turmas as $turma) {
            echo "<button data-id='$turma->id'>$turma->nome</button>";
            array_push($retorno, ['id' => $turma->id, 'nome' => $turma->nome]);
        }
        echo "</div><div id='turma_options'>
            <form action='$url' class='d-none editar_turma' method='POST'>
                <div style='max-width: 200px; text-align: left;'>
                    <input name='nome' value='' title='Nome turma'\>
                </div>
                
                <input type='hidden' name='turma_id' value='0' title='Escolha uma turma'\>
                <input type='hidden' name='action' value='update_turma'\>
                <div style='max-width: 100px; text-align: left;'>
                    <input type='submit' id='btnEditarTurma' value='Mudar nome'\>
                </div>
            </form>
            <form action='$url' class='d-none excluir_turma' method='POST'>
                <input type='hidden' name='turma_id' value='0' title='Escolha uma turma'\>
                <input type='hidden' name='action' value='delete_turma'\>
                <div style='max-width: 100px; text-align: left;'>
                    <input type='submit' id='btnExcluirTurma' value='Excluir'\>
                </div>
            </form>
        </div>";
        echo "</div>";
        echo "<table id='turma_table'>
            <thead>
                <th>Nome</th>
                <th>Código Escola</th>
                <th>Código Estudante</th>
                <th>Opções</th>
            </thead>
            <tbody>";
        foreach ($alunos as $a) {
            echo "
                <tr class=" . "turma_" . $a->turmas_id . " style='display: none;'>
                    <td>
                    <span class='nome_aluno'>$a->nome</span>
                    <form action='$url' method='POST' class='form_editar_aluno' style='display: none;'>
                        <input value='$a->nome' name='nome'>
                        <input type='hidden' value='$a->id' name='aluno_id'>
                        <input type='hidden' value='update_aluno' name='action'>
                        <input class='editar_aluno' type='submit' value='Salvar' style='background-color: #00b7c5; color: black;'>
                        <input class='cancelar_form_aluno' type='button' value='Cancelar' style='background-color: #ccc; color: black;'>
                    </form>
                    </td>
                    <td>" . um_profile_id() . "</td>
                    <td>$a->codigo</td>
                    <td>
                    <form action='$url' method='POST'>
                        <input type='hidden' value='$a->id' name='aluno_id'>
                        <input type='hidden' value='delete_aluno' name='action'>
                        <input class='excluir_aluno' type='submit' value='Excluir' style='background-color: red; color: white;'>
                    </form>
                    <input class='editar_aluno' type='button' value='Editar' style='background-color: #00b7c5; color: black;'>
                    </td>
                </tr>";
        }

        echo "</tbody></table></div>";
    }
}

add_shortcode('form_cadastradas_turma', '_render_turma_table');

//Export arquivo .CSV
function array2csv(array &$array)
{
    if (count($array) == 0) {
        return null;
    }
    ob_start();
    $df = fopen("php://output", 'w');
    fputcsv($df, array_keys(reset($array)));
    foreach ($array as $row) {
        fputcsv($df, $row);
    }
    fclose($df);
    return ob_get_clean();
}
/*
function download_send_headers($filename)
{
    // disable caching
    $now = gmdate("D, d M Y H:i:s");
    header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
    header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
    header("Last-Modified: {$now} GMT");

    // force download
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");

    // disposition / encoding on response body
    header("Content-Disposition: attachment;filename={$filename}");
    header("Content-Transfer-Encoding: binary");
}

download_send_headers("data_export_" . date("Y-m-d") . ".csv");
echo array2csv($array);
die();

*/
function _get_turma_all($is_shop_modal = false)
{
    global $wpdb;
    if (_get_role() == "um_escola") {
        $turmas = 0;
        if ($is_shop_modal == true) {
            $turmas = $wpdb->get_results("SELECT wp_turmas.id,wp_turmas.nome FROM wp_turmas join wp_alunos on wp_turmas.id = wp_alunos.turmas_id where user_id = " . um_profile_id() . " group by nome;");
        } else {
            $turmas = $wpdb->get_results("SELECT * FROM wp_turmas where user_id = " . um_profile_id() . ";");
        }

        $retorno = array();
        foreach ($turmas as $turma) {
            //$retorno .= "<p>ID: ".$turma->id." TURMA: ".$turma->nome."</p>";
            array_push($retorno, ['id' => $turma->id, 'nome' => $turma->nome]);
        }
        return $retorno;
    }
}

add_action('wp_head', 'queue_assets_turma');


function queue_assets_turma()
{
    wp_enqueue_style('tonzera', plugin_dir_url(__FILE__) . 'assets/css/tonzera.min.css');
    wp_enqueue_script('tonzera', plugin_dir_url(__FILE__) . 'assets/js/form.js');
}

// Cadastrar turmas
function post_turma()
{
    global $wpdb;

    echo "Teste submit post <br>";
    $user_id = um_profile_id();
    if (!session_id()) {
        session_start();
    }

    if (_get_role() == "um_escola") {

        foreach ($_POST['nome'] as $nome) {
            $values = array('nome' => strtoupper($nome), 'user_id' => $user_id);
            $wpdb->insert('wp_turmas', $values);
        }
        $_SESSION['mensagens'][] = array('type' => 'success', 'message' => 'Turmas cadastradas');

    //$turmas = $wpdb->get_results( "SELECT * FROM wp_turmas where user_id = ". um_profile_id() .";" );
    //foreach ( $turmas as $turma ) {
    //echo $turma->nome. '<br>';
    //}
        wp_redirect('/user');
    } else {

        wp_redirect('/');
        exit();
    }
}

//add_shortcode( 'form_turma_submit','post_turma');

add_action("admin_post_add_turma", 'post_turma');

//function para deletar turma
function delete_turma()
{
    global $wpdb;

    var_dump($_POST);
    $user_id = um_profile_id();
    if (!session_id()) {
        session_start();
    }
    if (_get_role() == "um_escola" && isset($_POST['turma_id'])) {

        $where = array('id' => $_POST['turma_id'], 'user_id' => $user_id);

        $wpdb->delete('wp_turmas', $where);
        $_SESSION['mensagens'][] = array('type' => 'success', 'message' => 'Turma deletada com sucesso');

        wp_redirect('/user');
    } else {
        $_SESSION['mensagens'][] = array('type' => 'success', 'message' => 'Não foi possível realizar a ação');
        wp_redirect('/');
        exit();
    }
}

add_action("admin_post_delete_turma", "delete_turma");
//function para editar nome da turma
function update_turma()
{
    global $wpdb;

    var_dump($_POST);
    $user_id = um_profile_id();
    if (!session_id()) {
        session_start();
    }
    if (_get_role() == "um_escola" && isset($_POST['turma_id'])) {
        $values = array('nome' => trim(strtoupper($_POST['nome'])));
        $where = array('id' => $_POST['turma_id'], 'user_id' => $user_id);
        $wpdb->show_errors();
        $wpdb->update('wp_turmas', $values, $where);
        $wpdb->show_errors();
        $_SESSION['mensagens'][] = array('type' => 'success', 'message' => 'Nome da turma alterado para: ' . $_POST['nome']);

        wp_redirect('/user/um_escola/#turmas_cadastradas');
    } else {
        $_SESSION['mensagens'][] = array('type' => 'success', 'message' => 'Não foi possível realizar a ação');
        wp_redirect('/');
        exit();
    }
}

add_action("admin_post_update_turma", "update_turma");

function delete_aluno()
{
    global $wpdb;

    $user_id = um_profile_id();
    if (!session_id()) {
        session_start();
    }

    if (_get_role() == "um_escola" && isset($_POST['aluno_id'])) {
        $id = $_POST['aluno_id'];
        $values = array('id' => $id, 'escola_id' => $user_id);
        $wpdb->delete('wp_alunos', $values);
        $_SESSION['mensagens'][] = array('type' => 'success', 'message' => 'Aluno: ' . $_POST['nome'] . ' excluído');
        wp_redirect('/user/um_escola/#turmas_cadastradas');
    } else {
        wp_redirect('/');
        exit();
    }
}

add_action("admin_post_delete_aluno", 'delete_aluno');

//function para editar nome do aluno
function update_aluno()
{
    global $wpdb;

    $user_id = um_profile_id();
    if (!session_id()) {
        session_start();
    }

    if (_get_role() == "um_escola" && isset($_POST['aluno_id'])) {
        $id = $_POST['aluno_id'];
        $nome = strtoupper($_POST['nome']);
        $values = array('nome' => $nome);
        $where = array('id' => $id, 'escola_id' => $user_id);

        $wpdb->update('wp_alunos', $values, $where);
        $_SESSION['mensagens'][] = array('type' => 'success', 'message' => 'Nome do aluno foi atualizado');
        wp_redirect('/user/um_escola/#turmas_cadastradas');
    } else {
        wp_redirect('/');
        exit();
    }
}

add_action("admin_post_update_aluno", 'update_aluno');


// function criada para exibir turmas após cadastramento
function render_form_turma_cadastradas()
{
    global $wpdb;
    $turmass = $wpdb->get_results("SELECT id,nome FROM wp_turmas where user_id = " . um_profile_id() . ";");
    $retorno = array();

    if (count($turmass) > 0) {
        echo "<p>Veja as turmas já adicionadas, para deletar uma turma basta clicar na mesma.</p>";
    }
    if (count($turmass) == 0) {
        echo "<p style='color: red;'>Você ainda não tem turmas cadastradas</p>";
    }
    echo "<div id='turma_buttons'>";
    foreach ($turmass as $turma1) {
        echo "<button data-id='$turma1->id'>$turma1->nome</button>";
        array_push($retorno, ['id' => $turma1->id, 'nome' => $turma1->nome]);
    }
    echo "</div>
    <div style='width: 100%; text-align: left; float: left;'>
        <form action='$url' class='d-none editar_turma' method='POST' style='float: left;'>                
                    <input name='nome' value='' title='Nome turma'\> 
                <input type='hidden' name='turma_id' value='0' title='Escolha uma turma'\>
                <input type='hidden' name='action' value='update_turma'\>               
        </form>  
        <form action='$url' class='d-none excluir_turma' method='POST' style='float: left; margin-left: 10px;'>
                <input type='hidden' name='turma_id' value='0' title='Escolha uma turma'\>
                <input type='hidden' name='action' value='delete_turma'\>
                    <input type='submit' id='btnExcluirTurma' value='Excluir'\>
                
        </form>
    </div>
        ";
}


// function create form + buttons
function render_form_turma()
{
    global $wp;

    //$url = home_url( add_query_arg( array(), $wp->request ) )."/";
    $url = esc_url(admin_url('admin-post.php'));
    if (_get_role() == "um_escola") {
        //_render_turma_table();
        _get_turma_all();
        ?>
        <div>
            <div>
                <form action='<?php echo $url ?>' method='POST' class='vc_col-12'>
                    <button type="button" id="addTurma">Adicionar Turmas</button>
                    <button type="button" id="removeTurma">Remover Turmas</button>
                    <div id="turmas">
                        <div class="turma" style="text-align: left;">
                            <label for="nome1">Nome da 1ª Turma:</label>
                            <input required id="nome1" name='nome[1]' placeholder="Turma 1 - A" \><br>
                        </div>
                    </div>
                    <p style='text-align: left; display: none;'><input type='hidden' name='action' value='add_turma' \>
                    </p>
                    <p style='text-align: left;'><input name='submit' type='submit' value='Salvar' \></p>
                </form>
            </div>
        </div>
        <?php
        render_form_turma_cadastradas();
    }
}

add_shortcode('form_turma', 'render_form_turma');
?>