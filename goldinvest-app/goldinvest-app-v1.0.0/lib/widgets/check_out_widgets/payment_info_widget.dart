import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../backend/local_storage/local_storage.dart';
import '../../controller/checkout/checkout_controller.dart';
import '../../controller/delivery_address/delivery_address_controller.dart';
import '../../custom_assets/assets.gen.dart';
import '../../languages/strings.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../utils/size.dart';
import '../common/text_labels/title_heading2_widget.dart';
import '../custom_card_without_status/custom_card_without_status.dart';

class PaymentInfoWidget extends StatelessWidget {
  PaymentInfoWidget({super.key});
  final CheckoutController checkoutController = Get.put(CheckoutController());
  final DeliveryAddressController deliveryAddressController =
      Get.put(DeliveryAddressController());

  @override
  Widget build(BuildContext context) {
    return Obx(
      () => Padding(
        padding: EdgeInsets.only(top: Dimensions.paddingSize * 1.5),
        child: Column(
          crossAxisAlignment: crossStart,
          children: [
            Padding(
              padding: EdgeInsets.only(bottom: Dimensions.heightSize),
              child: const TitleHeading2Widget(text: Strings.paymentType),
            ),
            GestureDetector(
              onTap: () {
                checkoutController.selectCard(
                    0, checkoutController.userWallet.value);
              },
              child: Card(
                borderOnForeground: true,
                shadowColor: CustomColor.blackColor,
                child: WalletInfoWidget(
                  backgroundColor:
                      checkoutController.selectedCardIndex.value == 0
                          ? CustomColor.blackColor.withValues(alpha: 0.05)
                          : CustomColor.whiteColor,
                  title: "${checkoutController.userWallet}",
                  balance:
                      "${checkoutController.availableBalance} ${LocalStorage.baseCurrencyCode}",
                  balanceDescription: Strings.availableBalance,
                  imagePath: Assets.icon.wallet2,
                ),
              ),
            ),
            verticalSpace(Dimensions.widthSize),
            GestureDetector(
              onTap: () {
                checkoutController.selectCard(
                    1, checkoutController.cashOnDelivery.value);
              },
              child: Card(
                child: WalletInfoWidget(
                  backgroundColor:
                      checkoutController.selectedCardIndex.value == 1
                          ? CustomColor.blackColor.withValues(alpha: 0.05)
                          : CustomColor.whiteColor,
                  title: "${checkoutController.cashOnDelivery}",
                  textColor: CustomColor.liteBlack2,
                  imagePath: Assets.icon.money3,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
