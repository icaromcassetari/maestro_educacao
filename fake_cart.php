<?php

/**
 * 
 * Documento responsavel por adicionar alunos e adicionar produtos ao carrinho ficticio
 * O carrinho ficticio é está alocado às variaveis de sessão nativa do PHP.
 * É possivel ver o carrinho na página de usuario
 * Após confirmar o carrinho, pode-se realizar um pedido, este pedido não faz parte da base de dados
 * do WooCommerce, é um pedido especifico para este plugin (tonzera). Todos os pais poderão 
 * comprar estes pedidos;
 * 
 */
/**
 * WooCommerce changes
 * Adaptação do woocommerca para aplicar o que foi dito na reunião
 */

//add_action( 'woocommerce_single_product_summary', 'my_extra_button_on_product_page', 30 );
//add_action( 'woocommerce_after_add_to_cart_quantity', 'my_extra_button_on_product_page', 30 ); -> hook secreto que nao ta na documentacao
//add_action( 'woocommerce_shop_loop', 'my_extra_button_on_product_page', 10, 0 ); 
/*function my_extra_button_on_product_page() {
  global $product;
  //var_dump($product['products']['name']);
  //echo '<a href="#">Extra Button</a>';
}*/
?>
<?php
// Limpa carrinho depois que o usuario pai loga
add_action('um_on_login_before_redirect', 'my_on_login_before_redirect', 10, 1);
function my_on_login_before_redirect($user_id)
{
    if (_get_role() == "um_pais" && count(_get_produtos_diff(true)) > 0) {
        global $woocommerce;
        $woocommerce->cart->empty_cart();
    }
}

// Devolve os produtos para nao aparecerem no loop da pagina de venda
function _get_produtos_diff($return = false)
{
    global $wpdb;
    // itens pedidos

    /*$itens_pedidos = $wpdb->get_results( "select wp_itens_pedidos.*, wp_alunos.nome, wp_alunos.escola_id from wp_itens_pedidos
                            join wp_alunos on wp_itens_pedidos.aluno_id = wp_alunos.id 
                            where wp_alunos.pais_id = ".intval(um_profile_id())." and pai_comprou = '0';");
    */
    $itens_pedidos = $wpdb->get_results("select wp_itens_pedidos.*, wp_alunos.nome, wp_alunos.escola_id, data_limite, datediff(data_limite,date(now())) as datediff from wp_itens_pedidos
    join wp_alunos on wp_itens_pedidos.aluno_id = wp_alunos.id
    join wp_pedidos on wp_pedidos.id = wp_itens_pedidos.pedido_id
    where wp_alunos.pais_id = " . intval(um_profile_id()) . " and pai_comprou = '0' and data_limite >= date(now());");
    if ($return) return $itens_pedidos;
    //var_dump($itens_pedidos);
    /*$id_lista_pai = [];
    foreach($produtos as $p){
        $id_lista_pai[] = $p->produto_id;
    }

    // id de todos os produtos
    $all_ids = get_posts( array(
        'post_type' => 'product',
        'numberposts' => -1,
        'post_status' => 'publish',
        'fields' => 'ids',
    ) );
    $id_produtos = [];

    foreach ( $all_ids as $id ) {
            $id_produtos[] = $id;
    }*/

    // listar apenas produtos que nao estao no carrinho
    global $woocommerce;
    $items_cart;
    try {
        $items_cart = $woocommerce->cart->get_cart();
    } catch (Error $e) { }

    //var_dump($items_cart);
    if (isset($items_cart)) {
        foreach ($items_cart as $item => $values) {

            foreach ($itens_pedidos as $key => $value) {
                //if ($itens_pedidos[$key] == $items_cart[$item]['product_id']){
                /*$array_id_pedidos = array_map(function($a){
                    return intval($a);
                },explode(",",$items_cart[$item]['item_pedido_id']));*/ // bagui mt loco pra converte tudo pra int

                if (intval($value->id) == intval($items_cart[$item]['item_pedido_id'])) {
                    unset($itens_pedidos[$key]);
                }
            }
        }
    }

    return $itens_pedidos;
}

// retorna apenas os ID's de itens pedidos para formar meta_data da venda e entao mostrar os pais que compraram ou nao
function _find_itens_pedidos($id)
{
    $ip = _get_produtos_diff(true);
    foreach ($ip as $key => $value) {
        //if ($itens_pedidos[$key] == $items_cart[$item]['product_id']){
        /*$array_id_pedidos = array_map(function($a){
            return intval($a);
        },explode(",",$items_cart[$item]['item_pedido_id']));*/ // bagui mt loco pra converte tudo pra int

        if (intval($value->id) == intval($id)) {
            return $value;
        }
    }

    return "";
}

// pega os itens pedidos e coloca em arrays
function _itens_pedidos_quantity_array()
{
    global $wpdb;
    $produtos = $wpdb->get_results("select * from wp_itens_pedidos as ip 
                    join wp_alunos as al on ip.aluno_id = al.id 
                    where al.pais_id = " . um_profile_id() . ";");

    $array = array();

    foreach ($produtos as $p) {
        $array[$p->produto_id] = ['quantidade' => $p->quantidade];
    }

    return $array;
}

// tira a quantidade para nao poder alterar
function input_args($args, $product)
{

    if (_get_role() == "um_pais" && count(_get_produtos_diff(true)) > 0) {
        $args['input_value'] = _itens_pedidos_quantity_array()[$product->id]['quantidade'];
        $args['classes'][] = 'd-none';
    }

    return $args;
}
add_filter('woocommerce_quantity_input_args', 'input_args', 10, 2);

// Verifica se o produto está na lista para o filho, se nao tiver, redireciona pra loja
// e nao deixa o pai adicionar ao cart
function my_custom_add_to_cart($cart_item_key, $product_id)
{
    $pedido_id;

    if (_get_role() == "um_pais" && count(_get_produtos_diff(true)) > 0) {
        if (!session_id()) {
            session_start();
        }

        $item_pedido = $_GET['item_pedido_id'];
        global $wpdb;

        // checa se passou da data limite
        $dataLimite = $wpdb->get_results("SELECT data_limite FROM wp_itens_pedidos 
        join wp_pedidos on wp_pedidos.id = wp_itens_pedidos.pedido_id 
        join wp_alunos on wp_alunos.id = wp_itens_pedidos.aluno_id  
        where wp_itens_pedidos.id = " . $item_pedido . " and pais_id = " . um_profile_id() . " limit 1;")[0]->data_limite;

        $now = date("Y-m-d");


        if (!in_array(strval($product_id), (array) array_column(_get_produtos_diff(true), 'produto_id'))) {
            $_SESSION['mensagens'][] = array('type' => 'error', 'message' => 'Você não pode adicionar este produto ao carrinho');
            wp_redirect('/wordpress/venda');
            exit();
        } elseif (!(strtotime($dataLimite) >= strtotime($now))) {
            $_SESSION['mensagens'][] = array('type' => 'error', 'message' => 'Acabou a data de compras da escola.<br>Entre em contato com escola do seu filho');
            wp_redirect('/wordpress/venda');
            exit();
        }
    }
}

add_action('woocommerce_add_to_cart', 'my_custom_add_to_cart', 10, 2);

// redireciona para a loja depois que o pai adiciona produto no carrinho
function my_custom_add_to_cart_redirect($url)
{
    if (_get_role() == "um_pais") {
        return 'http://localhost/wordpress/venda';
    }
}
add_filter('woocommerce_add_to_cart_redirect', 'my_custom_add_to_cart_redirect');

// não mostra nenhum produto para os pais, 
//os produtos sao renderizados para os pais pelo shortcode [produtos_para_aluno]
function parents_produts_only($q)
{

    /*if (!empty(_array_produtos_diff()['post_in']) ){
        $q->set( 'post__in', _array_produtos_diff()['post_in'] ); 
    }
    else{
        $q->set( 'post__not_in', _array_produtos_diff()['post_not_in'] ); 
    }*/
    if (_get_role() == 'um_pais' && count(_get_produtos_diff(true)) > 0) $q->set('post__in', array(0));

    remove_action('woocommerce_no_products_found', 'wc_no_products_found');
}
add_action('woocommerce_product_query', 'parents_produts_only');

/**
 * Adiciona custom data ao carrinho, para validar os itens_pedidos que estão no carrinho
 * @param  [type] $cart_item_data [description]
 * @param  [type] $product_id     [description]
 * @param  [type] $variation_id   [description]
 * @return [type]                 [description]
 */
function custom_data_cart($cart_item_data, $product_id, $variation_id)
{
    if (isset($_REQUEST['item_pedido_id'])) {
        $cart_item_data['item_pedido_id'] = $_REQUEST['item_pedido_id']; //$cart_item_data['item_pedido_id'] . "," . 
    }

    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'custom_data_cart', 10, 3);

/**
 * Display information as Meta on Cart page
 * @param  [type] $item_data [description]
 * @param  [type] $cart_item [description]
 * @return [type]            [description]
 */
function my_custom_cart_fields($item_data, $cart_item)
{
    if (array_key_exists('item_pedido_id', $cart_item)) {
        $custom_details = _find_itens_pedidos($cart_item['item_pedido_id']);

        $item_data[] = array(
            'key'   => 'Nome filho',
            'value' => $custom_details->nome
        );
    }

    return $item_data;
}
add_filter('woocommerce_get_item_data', 'my_custom_cart_fields', 10, 2);

// adiciona os itens pedidos do plugin para Maestro ao pedido do woocommerce
// atualiza tabela wp_itens_pedidos quando confirma o checkout 
// wc-on-hold = aguandando
// wc-complete = completo
function my_checkout($order_id)
{
    global $wpdb;
    if (!empty($_POST['itens_pedidos']) && _get_role() == "um_pais") {

        update_post_meta($order_id, 'Itens Pedidos (maestro plugin) ID', sanitize_text_field($_POST['itens_pedidos']));
        /*
        $ids = explode(",",$_POST['itens_pedidos']);
        foreach ($ids as $id){
            $wpdb->update( 'wp_itens_pedidos', array('pai_comprou' => 'wc-on-hold'), array('id' => intval($id)) );
        }*/
    }
}
add_action('woocommerce_checkout_update_order_meta', 'my_checkout');

function my_custom_checkout_field_display_admin_order_meta($order)
{
    if (get_post_meta($order->id, 'Itens Pedidos (maestro plugin) ID', true) !== null) {
        echo '<p><strong>' . __('Itens Pedidos (maestro plugin) ID') . ':</strong> ' . get_post_meta($order->id, 'Itens Pedidos (maestro plugin) ID', true) . '</p>';
    }
}
add_action('woocommerce_admin_order_data_after_billing_address', 'my_custom_checkout_field_display_admin_order_meta', 10, 1);

function status_compra_completed($order_id)
{
    global $wpdb;
    $ids = explode(",", get_post_meta($order_id, 'Itens Pedidos (maestro plugin) ID', true));
    foreach ($ids as $id) {
        $wpdb->update('wp_itens_pedidos', array('pai_comprou' => 'wc-complete'), array('id' => intval($id)));
    }
}
add_action('woocommerce_order_status_completed', 'status_compra_completed');

function status_compra_on_hold($order_id)
{
    global $wpdb;
    $ids = explode(",", get_post_meta($order_id, 'Itens Pedidos (maestro plugin) ID', true));
    foreach ($ids as $id) {
        $wpdb->update('wp_itens_pedidos', array('pai_comprou' => 'wc-on-hold'), array('id' => intval($id)));
    }
}
add_action('woocommerce_order_status_on-hold', 'status_compra_on_hold');

/* Verifica se pai pode ver o produto
function product_single_check(){
    echo get_the_ID();
}
add_action("woocommerce_before_single_product_summary","product_single_check");
*/

/*
// funcao pra testar os bagui, tava dando erro e nao mostrava mensagem de erro kk
function teste(){
    global $wp;
    global $wpdb;

    // checa se passou da data limite
    $dataLimite = $wpdb->get_results("SELECT data_limite FROM wp_itens_pedidos 
    join wp_pedidos on wp_pedidos.id = wp_itens_pedidos.pedido_id 
    join wp_alunos on wp_alunos.id = wp_itens_pedidos.aluno_id  
    where wp_itens_pedidos.id = 20 and pais_id = ".um_profile_id()." limit 1;");
    $pageAtual = add_query_arg( array(), $wp->request );
    if($pageAtual == "venda"){
        //var_dump((array)_get_produtos_diff(true)[0]);
        //var_dump( in_array("119", (array) array_column(_get_produtos_diff(true),'produto_id')) );
        //global $wpdb;
        //$wpdb->show_errors();
        //$wpdb->delete( 'wp_turmas', array('id' => 8) );
        
        var_dump(strtotime("2019-10-09") >= strtotime("2019-10-08"));
        echo "<br><br>";
        var_dump($dataLimite);
    }
}
add_action('wp','teste');
*/
// ------------------------------------------------------------------------------------------------------
function nome_produto_woocommerce(){
    $titulo = the_title('', '', false);
    echo $titulo;
}
// Abaixo é tudo coisa do wordpress e "front-end"

// escola adiciona produto ao 'fake_cart' na página do produto
function action_woocommerce_before_cart(){
    $titulo = the_title('', '', false);
    $url = esc_url(admin_url('admin-post.php'));
    $turmas = _get_turma_all(true);
    if (_get_role() == 'um_escola') { ?>
        <button id='btnModal' style='margin-bottom: 10px' type='button' data-id='<?php echo get_the_ID() ?>'>Adicionar à lista para os pais</button></a><br>
        <?php
            }
            if (_get_role() == "um_pais" && count(_get_produtos_diff(true)) > 0) {
                wp_redirect('/wordpress/venda');
            }
        }

        add_action('woocommerce_single_product_summary', 'action_woocommerce_before_cart');

// escola adiciona produto ao 'fake_cart' na página loja
function before_after_btn( $add_to_cart_html, $product, $args ){
    global $wpdb;
    $titulo = the_title('', '', false);
    $url = esc_url( admin_url('admin-post.php'));

    if (_get_role() == "um_escola"){        
        $before = "<button class='classBtnModal' style='margin-bottom: 10px' type='button' data-id='". get_the_ID() ."'>Adicionar à lista para os pais</button></a><br>
        <div id='myModal' class='modal'>
        <div class='modal-content'>
        <div class='modal-header'>
        <h3>Adicione o livro para os pais</h3>
        <span id='closeModal' class='close'>&times;</span>
        </div>
        <form action='$url' method='POST' class='vc_col-12'>
        
        <p style='text-align: left'> <b>É muito fácil adicionar um produto à lista, siga os passos abaixo:</b> <br>
        1) Selecione uma turma<br>
        2) Insira a quantidade <br>
        3) Salve</p>
        <p style='text-align: left'>
        <label style='text-align: left' for='turma'>Turma para este material</label><br>
        <select style='text-align: left' id='turma' name='turma' required>
        <option value=''>Selecione uma turma</option>";
        ?><?php foreach(_get_turma_all(true) as $turma){
            $before .= "<option value='{$turma['id']}'>{$turma['nome']}</option>";
        } 
        ?><?php $before .= "</select><br><br>
        <label style='text-align: left' for='quantidadePorAluno'>Quantidade deste item POR ALUNO</label><br>
        <input style='text-align: left' type='text' id='quantidadePorAluno' name='quantidade' value='1' require/>
        <p><input type='hidden' id='idModalProduto' name='id' value=''\></p>
        <p><input type='hidden' name='action' value='add_item_to_pedido'\></p>
        <p style='text-align: left'><input name='submit' type='submit' value='Salvar'\></p>
        </p>
        </form>
        </div>
        </div>";
        $after = ''; 
        
        return $before . $add_to_cart_html . $after;
    }
    if (_get_role() == "um_pais"){
        return '' . "<a href='http://localhost/wordpress/venda/?add-to-cart=".$product->id."&quantity="._itens_pedidos_quantity_array()[$product->id]['quantidade']."' class='button product_type_simple add_to_cart_button ajax_add_to_cart'>Comprar</a>". '';
    }
    return ''.$add_to_cart_html.'';

}
add_filter( 'woocommerce_loop_add_to_cart_link', 'before_after_btn', 10, 3 );

// aqui não vai deixar mostrar o produto que não está na lista para os pais que a escola criou
        // também vai retirar os produtos dessa lista que estão no carrinho

        /*function custom_pre_get_posts_query( $q ) {
    
    if ( ! $q->is_main_query() ) return;
    if ( ! $q->is_post_type_archive() ) return;
    // só mostra os produtos que estão na lista dos pais
    
    if ($q->query_vars['post_type'] == "product" && _get_role() == "um_pais" && is_shop() ) {
        $q->set('post_type', 'product');
        $q->set( 'post__not_in', _array_produtos_diff()); 
        
        
    }
}
add_action( 'pre_get_posts', 'custom_pre_get_posts_query' );*/

        /*function parents_products_only(){?>
    <?php $_product = wc_get_product( 53 ); var_dump($_product); if (have_posts() && _get_role() == "um_pais") : ?>
    <?php while (have_posts()) : the_post(); ?>
        <div class="post">
            <?php  ?>
            <h1 class="title"><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title(); ?>"><?php the_title(); ?></a></h1>
            <div class="entry"><?php the_excerpt(); ?></div>
        </div>
    <?php endwhile; endif; ?>
    <?php if (have_posts() && _get_role() == "um_pais") : ?>
    <?php while (have_posts()) : the_post(); ?>
        <div class="post">
            <?php  ?>
            <h1 class="title"><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title(); ?>"><?php the_title(); ?></a></h1>
            <div class="entry"><?php the_excerpt(); ?></div>
        </div>
    <?php endwhile; endif; ?>
<?php
    
}
add_shortcode( 'render_parents_products_only', 'parents_products_only' );*/
        /**
         * 
         * Tonzera
         */

        //
        function post_fake_cart()
        {
            if (!session_id()) {
                session_start();
            }
            // TODO -> VERIFICAR SE O PRODUTO EXISTE

            $id = absint($_POST['id']);
            $turma = absint($_POST['turma']);
            $quantidade = absint($_POST['quantidade']);

            try {
                if (isset($_SESSION['fake_cart'][$turma][$id])) { // ja existe
                    $_SESSION['fake_cart'][$turma][$id]['quantidade'] += $quantidade;
                } else {
                    $key_turma = array_search(strval($turma), array_column(_get_turma_all(), "id"));
                    $_product = wc_get_product($id);
                    $_SESSION['fake_cart'][$turma][$id] = array(
                        'id_produto' => $id,
                        'quantidade' => $quantidade,
                        'nome' => $_product->get_name(),
                        'preco' => $_product->get_price(),
                        'turma_nome' => _get_turma_all()[$key_turma]['nome']
                    );
                }
                $_SESSION['mensagens'][] = array('type' => 'success', 'message' => 'Pedido adicionado à lista com sucesso');
            } catch (Error $e) {
                $_SESSION['mensagens'][] = array('type' => 'error', 'message' => 'Não foi possível adicionar o pedido, tente novamente. <br> Se o erro persistir contate o suporte');
            }


            wp_redirect('https://www.maestroeducacao.com.br/venda');
            //exit();

        }
        add_action("admin_post_add_item_to_pedido", 'post_fake_cart');


        // aqui confirma o pedido e insere nos itens pedidos para os pais verem
        function create_pedidos()
        {
            global $wpdb;
            if (!session_id()) {
                session_start();
            }
            // TODO -> VERIFICAR SE O PRODUTO EXISTE
            // session pode ser cookie???? qual funcionaria melhor???
            if (_get_role() == "um_escola") {

                $data = $_POST['data_limite'];
                list($ano, $mes, $dia) = sscanf($data, '%d-%d-%d');
                list($nowAno, $nowMes, $nowDia) = sscanf(date("Y-m-d"), "%d-%d-%d");
                $now = $nowAno . "-" . $nowMes . "-" . $nowDia;
                $input = $ano . "-" . $mes . "-" . $dia;

                if (checkdate($mes, $dia, $ano) && (strtotime($input) >= strtotime($now))) {
                    $values = array('escola_id' => um_profile_id(), 'data_limite' => $input);
                    $pedido_id;
                    if ($wpdb->insert('wp_pedidos', $values)) {
                        $pedido_id = $wpdb->insert_id;
                        $_SESSION['mensagens'][] = array('type' => 'success', 'message' => 'Pedido cadastrado com sucesso');
                    }
                    //var_dump($_SESSION);

                    foreach ($_SESSION['fake_cart'] as $key => $a) {
                        foreach ($_SESSION['fake_cart'][$key] as $key2 => $b) {
                            $alunos = $wpdb->get_results("SELECT * FROM wp_alunos where escola_id = " . um_profile_id() . " AND turmas_id = " . $key . ";");
                            if (count($alunos) > 0) {
                                foreach ($alunos as $aluno) {
                                    $dados = array(
                                        'aluno_id' => $aluno->id,
                                        'produto_id' => $key2,
                                        'pedido_id' => $pedido_id,
                                        'quantidade' => $_SESSION['fake_cart'][$key][$key2]['quantidade'],
                                        'nome_produto' => $_SESSION['fake_cart'][$key][$key2]['nome'],
                                        'preco' => $_SESSION['fake_cart'][$key][$key2]['preco'],
                                        'turma' => $key
                                    );
                                    $result = $wpdb->insert('wp_itens_pedidos', $dados);
                                }
                            }
                        }
                    }
                    unset($_SESSION['fake_cart']);
                } else {
                    $_SESSION['mensagens'][] = array('type' => 'error', 'message' => 'Você não inseriu uma data correta');
                    var_dump($_SESSION);
                }
                wp_redirect('https://www.maestroeducacao.com.br/user');
                exit();
            }
        }
        add_action("admin_post_add_pedido_pais", 'create_pedidos');

        // renderiza a tabela dos produtos no 'fake_cart'
        function render_table_fake_cart()
        {
            global $wp;
            $pageAtual = add_query_arg(array(), $wp->request);
            if (!session_id()) {
                session_start();
            }
            if (_get_role() == "um_escola") {
                $url = esc_url(admin_url('admin-post.php'));

                if (isset($_GET['turma']) && isset($_GET['button'])) { //&& isset($_GET['submit'])){
                    unset($_SESSION['fake_cart'][$_GET['turma']][$_GET['button']]);
                    $_SESSION['mensagens'][] = array('type' => 'success', 'message' => 'Produto removido da lista');
                    if (empty($_SESSION['fake_cart'][$_GET['turma']])) {
                        unset($_SESSION['fake_cart'][$_GET['turma']]);
                    }
                    if (isset($_SESSION['fake_cart']) && count($_SESSION['fake_cart']) == 0) {
                        unset($_SESSION['fake_cart']);
                        wp_redirect('../venda');
                    }
                }
                if (isset($_SESSION['fake_cart'])) { ?>
            <div id="cart_plugin" style="text-align: center;">
                <h3>Lista para os pais</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Preço</th>
                            <th>Turma</th>
                            <th>Quantidade</th>
                            <th>Remover</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                                    foreach ($_SESSION['fake_cart'] as $key => $a) {
                                        foreach ($_SESSION['fake_cart'][$key] as $key2 => $b) {
                                            echo "
                        <tr>
                            <td>" . $_SESSION['fake_cart'][$key][$key2]['nome'] . "</td>
                            <td>" . $_SESSION['fake_cart'][$key][$key2]['preco'] . "</td>
                            <td>" . $_SESSION['fake_cart'][$key][$key2]['turma_nome'] . "</td>
                            <td>" . $_SESSION['fake_cart'][$key][$key2]['quantidade'] . "</td>
                            <td>
                            <form method='get'>
                            <input style='display: none;' value='" . $key . "' name='turma'>
                            <button value='" . $key2 . "' name='button'>Remover item</button>
                            </form>
                            </td>
                        </tr>";
                                        }
                                    } ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4">
                                <form action='<?php echo $url ?>' method='POST' enctype="multipart/form-data">
                                    <label for='data_limite'>Data Limite</label><br>
                                    <input type='date' id='data_limite' name='data_limite' \>
                                    <p style='text-align: center; display: none;'><input type='hidden' name='action' value='add_pedido_pais' \></p>
                                    <p style='text-align: center;'><input name='submit' type='submit' value='Finalizar pedido' \></p>
                                </form>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        <?php
                } elseif ($pageAtual == "venda") { } else { ?>
            <div id="cart_plugin" style="text-align: center;">
                <h3>Lista para os pais</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Preço</th>
                            <th>Turma</th>
                            <th>Quantidade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="text-align: center;">
                            <td colspan="4" style="text-align: center;"><span style="color: red; font-weight: 900;">Ainda não há nada na lista para os pais</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
<?php
        }
    }
}
add_shortcode('table_fake_cart', 'render_table_fake_cart');

/** Área dos Pais */
function render_produtos_para_aluno(){
    global $wpdb;
    //$url = home_url( add_query_arg( array(), $wp->request ) )."/";
    $produtos = _get_produtos_diff();
    $url = esc_url(admin_url('admin-post.php'));
    if (_get_role() == "um_pais" && count(_get_produtos_diff(true)) > 0) {
        $tabela = "<h3 style='text-align: center; color:#2222ff;'><b>Seu filho precisa destes produtos<b></h3><table>
        <h4 style='text-align: center'><b>Atenção!</b> <br> Quando um produto expira, você não pode mais comprá-lo, será necessário conversar com a escola de seu filho.</h4>
                    <thead>
                        <tr style='background-color: black; color: white;'>
                            <th style='background-color: black;'>Produto</th>
                            <th style='background-color: black;'>Preço</th>
                            <th style='background-color: black;'>Quantidade</td>
                            <th style='background-color: black;'>Expira em</th>
                            <th style='background-color: black;'>Adicionar ao carrinho</th>
                        </tr>
                    </thead>
                    <tbody>";

        foreach ($produtos as $key => $produto) {

            $tabela .= //se tiver no cart não pode aparecer.
                "
            <tr>
                <td>" . $produto->nome_produto . "</td>
                <td>" . $produto->preco . "</td>
                <td>" . $produto->quantidade . "</td>
                <td><b>" . $produto->datediff . " dias</b></td>
                <td><a href='https://www.maestroeducacao.com.br/area-dos-pais/?add-to-cart=" . $produto->produto_id . "&quantity=" . $produto->quantidade . "&item_pedido_id=" . $produto->id . "'>Adicionar</a></td>
            </tr>";
        }
        $tabela .= "
        </tbody>
        </table>";
        /*<tfoot>
        <tr>
            <td colspan='4'>
            <form action='<?php echo $url ?>' method='POST' enctype='multipart/form-data'>
                <p style='text-align: center; display: none;'><input type='hidden' name='action' value='add_pedido_pais'\></p>
                <p style='text-align: center;'><input name='submit' type='submit' value='Comprar todos'\></p>
            </form>
            </td>
        </tr>
    </tfoot>*/
        echo $tabela;
    }
}
add_shortcode('table_produtos_para_aluno', 'render_produtos_para_aluno');

/** CHECKOUT E OUTRAS COISAS PARA VERIFICAR SE O PAI COMPROU O OBJETO QUE A ESCOLA PEDIU */

// Form Checkout para os itens adicionados ao carrinho pelo plugin da Maestro.
function my_filter_checkout($fields)
{
    global $woocommerce;
    $items_cart = $woocommerce->cart->get_cart();

    //var_dump($items_cart);
    $ids_itens_pedidos = "";
    $cont = 1;
    $escola_id = 0;
    foreach ($items_cart as $item => $values) {

        if ($cont < count($items_cart)) $ids_itens_pedidos = $values['item_pedido_id'] . "," . $ids_itens_pedidos;
        else $ids_itens_pedidos .= $values['item_pedido_id'];

        $escola_id = _find_itens_pedidos($values['item_pedido_id'])->escola_id;

        $cont++;
    }
    um_fetch_user(intval($escola_id)); // ID da escola


    //TODO - VALIDAR SE É AQUELE TIPO DE COMPRA PRO FILHO
    if (_get_role() == "um_pais" && count(_get_produtos_diff(true)) > 0) {
        $woocommerce->customer->set_billing_location('', '', '', '');
        $woocommerce->customer->set_shipping_location('', '', '', '');

        $fields['billing']['billing_country']['class'][] = 'd-none';
        $fields['billing']['billing_city']['class'][] = 'd-none';
        $fields['billing']['billing_address_1']['class'][] = 'd-none';
        $fields['billing']['billing_postcode']['class'][] = 'd-none';
        $fields['billing']['billing_state']['class'][] = 'd-none';

        echo "<script>
            document.getElementById('billing_address_1').value = '" . um_user('billing_address_1') . "';
            document.getElementById('billing_city').value = '" . um_user('billing_city') . "';
            document.getElementById('billing_postcode').value = '" . um_user('billing_postcode') . "';
            document.getElementById('billing_state').value = '" . um_user('billing_state') . "';
        </script>";


        woocommerce_form_field('itens_pedidos', array(
            'type'          => 'text',
            'class'         => array('my-field-class form-row-wide d-none'),
            //'label'         => __('Fill in this field'),
            //'placeholder'   => __('Enter something'),
            'default' => $ids_itens_pedidos,
        ));



        unset($fields['billing']['billing_address_2']);
        $woocommerce->customer->set_billing_location('', '', '', '');

        return $fields;
    }
    return $fields;
}
add_filter('woocommerce_checkout_fields', 'my_filter_checkout', 10);
