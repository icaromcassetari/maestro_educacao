<?php
/**
 * Plugin Name: Maestro Educação
 * Author: Icaro Cassetari
 * Author URI:
Description: Plugin feito para MaestroEducação, desenvolvido para a venda de livros aos pais.
 * Version: 2.1
 */
defined( 'ABSPATH' ) || exit;

function myplugin_activate() {
    global $wpdb;

    $sql1 = "CREATE TABLE IF NOT EXISTS wp_alunos (
		id int(11) NOT NULL AUTO_INCREMENT,
		nome varchar(255) DEFAULT NULL,
		codigo varchar(45) DEFAULT NULL,
		pais_id bigint(20) unsigned DEFAULT NULL,
		turmas_id int(11) NOT NULL,
		escola_id bigint(20) unsigned NOT NULL,
		PRIMARY KEY  (id),
		KEY fk_alunos_pais1_idx (pais_id),
		KEY fk_alunos_turmas1_idx (turmas_id),
		KEY fk_alunos_escola (escola_id),
		CONSTRAINT  fk_alunos_escola FOREIGN KEY (escola_id) REFERENCES wp_users (ID) ON DELETE NO ACTION ON UPDATE NO ACTION,
		CONSTRAINT  fk_alunos_pais1_idx FOREIGN KEY (pais_id) REFERENCES wp_users (ID) ON DELETE NO ACTION ON UPDATE NO ACTION,
		CONSTRAINT  fk_alunos_turmas1_idx FOREIGN KEY (turmas_id) REFERENCES wp_turmas (id) ON DELETE CASCADE
	  ) CHARSET=latin1;";

    $sql2 = "CREATE TABLE IF NOT EXISTS wp_turmas (
		id int(11) NOT NULL AUTO_INCREMENT,
		nome varchar(191) DEFAULT NULL,
		user_id bigint(20) unsigned DEFAULT NULL,
		PRIMARY KEY  (id), 
		KEY fk_user (user_id),
		CONSTRAINT fk_user FOREIGN KEY (user_id) REFERENCES wp_users (ID)
	  ) CHARSET=latin1;";

    $sql3 = "CREATE TABLE IF NOT EXISTS wp_pedidos(
		id int(11) auto_increment,
		escola_id bigint(20) UNSIGNED,
		data_limite date,
		PRIMARY KEY  (id),
		KEY fk_escola_id2_idx (escola_id),
		CONSTRAINT fk_escola_id2_idx FOREIGN KEY (escola_id) REFERENCES wp_users (ID) ON DELETE NO ACTION ON UPDATE NO ACTION
	);";

    $sql4 = "CREATE TABLE IF NOT EXISTS wp_itens_pedidos(
		id bigint(20) AUTO_INCREMENT,
		aluno_id int(11),
		pedido_id int(11),
		produto_id bigint(20),
		quantidade int(11),
		nome_produto varchar(255),
		preco double(8,2),
		pai_comprou varchar(60) DEFAULT 0,
		turma int(11),
		PRIMARY KEY  (id),
		KEY fk_aluno_item_pedido (aluno_id),
		KEY fk_pedido_id (pedido_id),
		KEY fk_product_item_id (produto_id), 
		CONSTRAINT fk_aluno_item_pedido FOREIGN KEY (aluno_id) REFERENCES wp_alunos (id) ON DELETE CASCADE ON UPDATE NO ACTION,
		CONSTRAINT fk_pedido_id FOREIGN KEY (pedido_id) REFERENCES wp_pedidos (id) ON DELETE CASCADE ON UPDATE NO ACTION,
		CONSTRAINT fk_product_item_id FOREIGN KEY (produto_id) REFERENCES wp_wc_product_meta_lookup (product_id) ON DELETE NO ACTION ON UPDATE NO ACTION
	);";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql1 );
    dbDelta( $sql2 );
    dbDelta( $sql3 );
    dbDelta( $sql4 );
}
register_activation_hook(  __FILE__ , 'myplugin_activate' );

require_once( ABSPATH . 'wp-admin/includes/plugin.php' );


require_once 'aluno.php';
require_once 'fake_cart.php';
require_once 'turma.php';
require_once 'pais.php';
