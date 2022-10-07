# Plugin WooCommerce pour constituer des packs de bouteilles

Ce plugin permet d'appliquer des réductions automatiquement au panier dès que des packs de 3, 6 ou 12 unités sont constitués. A été développé spécifiquement pour la [brasserie Walpine](https://www.walpine.fr) et leurs spécificités liées au [logiciel de gestion de brasserie artisanale SuperG](https://www.superg.fr).

Les packs peuvent être constitués de plusieurs bières différentes, chacune ayant son SKU/UGS dédié pour facilité la gestion ensuite dans *SuperG*.

![Exemple de réduction appliqué à un panier de 6 bouteilles, 4 blondes, 1 IPA et 1 blanche](https://depot.studiomaiis.net/screenshots/walpine_panier.png "Exemple de réduction appliqué à un panier de 6 bouteilles, 4 blondes, 1 IPA et 1 blanche")

## Configuration requise

* PHP : 7.4 et plus
* WordPress : 5.6.9 et plus
* WooCommerce : 5.8 et plus
* Plugin ACF Pro

Il se peut que le plugin fonctionne sur des versions antérieures de PHP, WordPress ou WooCommerce, mais il n'a pas été testé dans d'autres conditions que celles mentionnées ci-dessus.

## Installation & mises à jour

La première installation est manuelle. [Téléchargez l'archive ici](https://depot.studiomaiis.net/wordpress/woocommerce-coupon-packs.zip) et installez le plugin de manière standard :
* via FTP une fois l'archive décompressée dans `wp-content/plugins/`,
* ou via la page d'administration des extensions.

Si vous téléchargez le code depuis Github, renommer le répertoire `wp-plugin-woocommerce-coupon-packs(*)` en `woocommerce-coupon-packs`.

Une fois le module installé, les mises à jour se font depuis le back-office de WordPress.

## Configuration

1. Allez dans *Marketing > Codes promo* et créez un nouveau code promo. Peut importe son nom mais *REMISE_PACKS* semble approprié pour une meilleure identification dans les commandes.

2. Sélectionnez *Packs* dans l'onglet *Général > Type de remise*.

3. Activez *Code promo pour la configuration des packs ?*

4. Saisissez vos tarifs dans la grille

![Page de configuration d'un code promo de type Packs](https://depot.studiomaiis.net/screenshots/woo_packs_config.png "Page de configuration d'un code promo de type Packs")

**Important** : il ne peut y avoir qu'un seul code promo de type *Packs*.

## Personnalisations

Ce plugin est assez spécifique à la [brasserie du Champsaur Walpine.fr](https://www.walpine.fr) mais grâce à ACF Pro, et moyennant quelques [développements WordPress sur mesure](https://www.studiomaiis.net), il peut être rapidement adapté à d'autres besoins.

N'hésitez pas à [me contacter créer votre plugin sur mesure !](https://www.studiomaiis.net).

