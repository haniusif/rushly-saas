<?php

return array (
  /*
   * Driver → customer WhatsApp intro, exposed as `wa_msg` on the parcel API.
   * Asterisks are WhatsApp bold markers.
   */
  'delivery_intro' => "Welcome – :customer,\n"
    . "I am the delivery agent from *:brand*, responsible for delivering your shipment No. *:shipment* from the store *:merchant*.\n\n"
    . "Your shipment will be delivered *today* to the following address:\n"
    . ":link\n\n"
    . "Please confirm your address either by sharing your *location* or any other method, and confirm your *availability at the delivery location today*.\n\n"
    . "*Shipment details:*\n"
    . "Collection amount: *:amount :currency*\n\n"
    . "*:brand*\n"
    . "A safe and reliable logistics partner.",
);
