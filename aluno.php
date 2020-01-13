<?php
function _get_role()
{
    $user = wp_get_current_user();
    return $roles = trim($user->roles[0]);
}

function _get_aluno_all()
{
    global $wpdb;
    if (_get_role() == "um_escola") {
        $turmas = $wpdb->get_results("SELECT * FROM wp_alunos where escola_id = " . um_profile_id() . ";");
        if (count($turmas) == 0) return "Ainda não possui alunos";

        $retorno = "";
        foreach ($turmas as $turma) {
            $retorno .= "<p>ID: " . $turma->id . " TURMA: " . $turma->nome . "</p>";
        }
        return $retorno;
    } else {
        return "Você não tem permissão";
    }
}

function queue_assets_alunos()
{
    // wp_enqueue_script( 'tonzera', plugin_dir_url( __FILE__ ) . 'assets/js/form.js' );
}
add_action('wp_head', 'queue_assets_alunos');
/* Cadastra turmas */
function post_aluno()
{
    global $wpdb;
    var_dump($_POST);
    echo "Teste submit post <br>";
    $user_id = um_profile_id();
    $str;
    if (!session_id()) {
        session_start();
    }

    if (!empty($_FILES['alunosCSV']['tmp_name'])) {
        //if ($_FILES['alunosCSV']['type'] == "application/vnd.ms-excel"){
        $str = file_get_contents($_FILES['alunosCSV']['tmp_name']);        
        $str = str_replace(";", "", $str);
        $re = '/.+\n/m';       

        preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);
        $str = $matches;
        echo $str . "<br>" . $re . "<br>" . $matches;  
        //}
    } elseif (_get_role() == "um_escola" && !empty($_POST['aluno_individual'])) {
        $turma = trim($_POST['turma']);


        $codigo = md5(microtime()); //md5(time());
        $codigo = substr($codigo, 9, 8) . $user_id . $turma;

        $values = array('nome' => strtoupper(trim($_POST['aluno_individual'])), 'turmas_id' => $turma, 'escola_id' => $user_id, 'codigo' => $codigo);
        $wpdb->insert('wp_alunos', $values);

        $aluno_id = $wpdb->insert_id;
        $itens_pedidos = $wpdb->get_results("select * from wp_itens_pedidos where turma = $turma group by pedido_id");
        if (sizeof($itens_pedidos) > 0) {
            foreach ($itens_pedidos as $item) {
                $values = array(
                    'aluno_id' => $aluno_id,
                    'pedido_id' => $item->pedido_id,
                    'produto_id' => $item->produto_id,
                    'nome_produto' => $item->nome_produto,
                    'preco' => $item->preco,
                    'turma' => $item->turma
                );
                $wpdb->insert('wp_itens_pedidos', $values);
            }
        }

        $_SESSION['mensagens'][] = array('type' => 'success', 'message' => 'Aluno cadastrado com sucesso');
        wp_redirect('/user/um_escola/#cadastrar_alunos');
        exit();
    }

    if (_get_role() == "um_escola") {
        $turma = trim($_POST['turma']);
        if (count($str) > 1) {
            for ($i = 1; $i < count($str); $i++) {
                $codigo = md5(microtime()); //md5(time());
                $codigo = substr($codigo, 9, 8) . $user_id . $turma;
                $values = array('nome' => strtoupper($str[$i][0]), 'turmas_id' => $turma, 'escola_id' => $user_id, 'codigo' => $codigo);
                //echo ' ---> nome = '. strtoupper($str[$i][0]) . ' escola_id = ' . $user_id . ' codigo = ' . $codigo . "<br>";
                //var_dump($values['nome']);
                $wpdb->insert('wp_alunos', $values);
                
            }
            $_SESSION['mensagens'][] = array('type' => 'success', 'message' => 'Alunos cadastrados com sucesso');

            /*$alunos = $wpdb->get_results( "SELECT * FROM wp_alunos where escola_id = ". um_profile_id() .";" );
            foreach ( $alunos as $aluno) {
                echo $aluno->nome. '<br>';
            }*/
            wp_redirect('/user/um_escola/#cadastrar_alunos');
            exit();
        }
    } else {
        $_SESSION['mensagens'][] = array('type' => 'error', 'message' => 'Não foi possível realizar o cadastro');
        wp_redirect('/user');
        exit();
    }
}
//add_shortcode( 'form_turma_submit','post_turma');

add_action("admin_post_add_aluno", 'post_aluno');
//add_action("admin_postv",'post_turma');
?>

<?php
// function create form + buttons
function render_form_aluno()
{
    global $wp;
    //$url = home_url( add_query_arg( array(), $wp->request ) )."/";
    $url = esc_url(admin_url('admin-post.php'));
    if (_get_role() == "um_escola") {
        _get_aluno_all();
        ?>

        <div style="text-align: left;">
            <div style="display: true;">
                <form action='<?php echo $url ?>' method='POST' class='vc_col-12' enctype="multipart/form-data">
                    <select id="turma" name="turma" required>
                        <option value="">Selecione uma turma</option>
                        <?php foreach (_get_turma_all() as $turma) {
                                    echo "<option value='{$turma['id']}'>{$turma['nome']}</option>";
                                }
                                ?>
                    </select><br>
                    <div style="width: 100%;">
                        <p for="aluno_individual">Nome do aluno:
                        <input type='text' id='aluno_individual' name='aluno_individual' \></p>
                    </div>
                    <div style="width: 100%;">
                        <p>Ou adicione vários alunos: <a href="https://maestroeducacao.com.br/modelo_de_planilha_para_inserir_alunos.csv">Segue modelo da planilha</a>
                        <input type="file" name="alunosCSV" accept=".csv" /></p>
                    </div>

                    <p style='text-align: left; display: none;'><input type='hidden' name='action' value='add_aluno' \></p>
                    <p style='text-align: left; margin-top: 10px;'><input name='submit' type='submit' value='Enviar' \></p>

                </form>
            </div>
        </div>
<?php
    }
}
add_shortcode('form_aluno', 'render_form_aluno');
