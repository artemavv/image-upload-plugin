# Plugin overview
The purpose of this plugin is to allow site visitors in a WooCommerce store  to generate their own images.

The generated image is composed of two images: one of the product and one of the user. 

Products are typically clothing items - something that is worn by a human. 

Typical use case: a visitor navigates onto a product page with some clothing item and uploads  an image of a person (a photo of himself, or of someone else who would wear that item). 

<img width="1086" height="679" alt="example" src="https://github.com/user-attachments/assets/a487eec9-0506-4fd0-92c9-7e4999d6809f" />



The plugin requests Image Generation API to actually generate the new image of a person (from uploaded image) who is wearing the clothing item (from product image).

Plugin allows you to send a product prompt to Image Generation API. This prompt is taken from the product field and is filled by site administrator. Additionally, administrator may allow site visitors to supply their own prompt (which is appended to the product prompt) when generating images.

# Plugin Settings

<img width="645" height="266" alt="dashboard" src="https://github.com/user-attachments/assets/7f0d5bce-4d68-4ef3-b482-1e0907bfbd50" />

The plugin provides a limited amount of image generation for site visitors. There are two separate usage limits:

* For registered site visitors (clients). Each client gets an individual amount of image generation available for him personally. 
Example: John and Mary are both registered in the WooCommerce store. Both John and Mary have 100 image generations per week, and usage by John does not change the amount of generation allowed to Mary. Mary can use her 100 image generations regardless of John’s usage.
* For unregistered site visitors (public users). Every unregistered visitor shared the usage limit with other unregistered visitors. 
Example: there are 1000 image generations available for unregistered users each week. If someone uses 500 image generations on the first day, then all other unregistered users share the remaining 500 image generations for the rest of the week.

Each usage limit is reset each day/week/month (this can be changed in plugin settings by Store Owner). Once the usage limit is reached, the user is not able to generate more images.

# Plugin Installation

To install the plugin, you must have Administrator privileges in a WooCommerce store. 

1. Go to Plugins -> Add new Plugin page in the Wordpress admin area.
2. Click “Upload plugin” button and select ZIP archive with the plugin.
3. When the plugin archive is uploaded and unpacked, you will see Image Uploading API plugin in the plugin list. Click “Activate” to turn it on.

**Warning**: If you upload plugin into some Wordpress installation which does not have an active WooCommerce plugin, you will see the notice: “This plugin cannot be activated because required plugins are missing or inactive”.

If this is the case, you need to install and activate WooCommerce plugin in order to be able to activate.

When Image Uploading API plugin is activated, you will see new menu item in “Tools” menu - 'Image Generation Dashboard'.


# Product settings for the image generation

When you have some products created in your WooCommerce store, you need to supply prompts and product images which will be used for image generation.

* Open product page in Wordpress admin area and scroll down to see the plugin settings box for that product:

  <img width="1600" height="752" alt="product_settings" src="https://github.com/user-attachments/assets/bccc8aba-058f-4635-ae75-a1e1eab022e0" />

  By default, image generation is enabled for all products, but you can manually disable it for the selected product (remove “Enable” check in the settings box).

* Under the “Enable” checkbox you will see the “Prompt” text field. You need to enter a short description of the product there, intended for the Image Generation API as an extra piece of information.

**Note**: image generation will be disabled for this particular product if the “Prompt” text field is empty.

By default, Image Generation API will use “Product Image” (indicated by the right red arrow on the screenshot above) as a source image for the new image. If you wish some other image to be used as a source, you can enter its URL in the “Image URL”.



