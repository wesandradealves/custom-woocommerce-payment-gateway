=== BDM Digital Payment Gateway ===
Contributors: douradocash  
Tags: pagamentos, pix, qr code, woocommerce, bdm  
Requires at least: 5.0  
Tested up to: 6.5  
Requires PHP: 7.4  
Stable tag: 1.0.0  
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  

Um plugin para processar pagamentos com BDM Digital via WooCommerce. Suporta geração de QR Codes, validação de transações e integração com carteiras digitais.

== Description ==

Este plugin permite que você aceite pagamentos com a moeda digital BDM em sua loja WooCommerce. Ele gera QR codes para pagamentos, realiza validações automáticas e fornece confirmações instantâneas para o cliente.

Principais funcionalidades:
* Geração automática de QR Code
* Validação de pagamento em tempo real
* Compatível com carteiras digitais BDM
* Integração direta com WooCommerce
* Página de checkout personalizada

== Installation ==

1. Faça o upload dos arquivos do plugin para o diretório `/wp-content/plugins/` ou instale diretamente pelo painel do WordPress.
2. Ative o plugin através do menu "Plugins" no WordPress.
3. Certifique-se de que o WooCommerce esteja instalado e ativado.
4. O plugin criará automaticamente uma página de checkout (`BDM Checkout`).
5. Crie Secret e Key para Woocommerce API Rest no menu de Configurações.
6. Configure os dados de integração no menu **Configurações > BDM Gateway**.

== Frequently Asked Questions ==

= É necessário ter WooCommerce instalado? =
Sim. Este plugin depende do WooCommerce para funcionar corretamente.

= Onde encontro os dados de integração com o BDM? =
Você pode obter os dados junto ao serviço da carteira BDM ou plataforma Dourado Cash.

== Screenshots ==

1. Tela de pagamento com QR Code
2. Página de checkout personalizada
3. Configurações no painel administrativo

== Changelog ==

= 1.0.0 =
* Versão inicial com integração completa com BDM Digital e WooCommerce

== Upgrade Notice ==

= 1.0.0 =
Primeiro lançamento oficial do plugin.

== License ==

Este plugin é software livre e está licenciado sob a GPLv2 ou posterior.
