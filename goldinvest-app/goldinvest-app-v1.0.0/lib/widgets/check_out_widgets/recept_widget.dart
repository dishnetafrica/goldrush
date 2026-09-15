import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../backend/local_storage/local_storage.dart';
import '../../controller/checkout/checkout_controller.dart';
import '../../controller/delivery_address/delivery_address_controller.dart';
import '../../languages/strings.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../utils/size.dart';
import '../common/text_labels/title_heading2_widget.dart';
import '../title_heading_row/title_heading_row.dart';

class ReceptWidget extends StatelessWidget {
  ReceptWidget({super.key});
  final CheckoutController checkoutController = Get.put(CheckoutController());
  final DeliveryAddressController deliveryAddressController =
      Get.put(DeliveryAddressController());

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(top: Dimensions.paddingSize),
      child: Column(
        crossAxisAlignment: crossStart,
        children: [
          verticalSpace(Dimensions.paddingSize),
          TitleHeading2Widget(
            text: Strings.receipt,
            fontSize: Dimensions.headingTextSize2 * 0.9,
            fontWeight: FontWeight.w800,
          ),
          Padding(
            padding: EdgeInsets.only(top: Dimensions.paddingSize * 0.5),
            child: TitleHeadingRow(
                leftHeading: Strings.amount,
                rightHeading:
                    "${checkoutController.goldPrice.value.toStringAsFixed(0)} ${LocalStorage.baseCurrencyCode}"),
          ),
          Divider(
            height: Dimensions.heightSize * 2.5,
            color: CustomColor.blackColor.withValues(alpha: 0.1),
            thickness: 1,
          ),
          TitleHeadingRow(
              leftHeading: Strings.deliveryCharge,
              rightHeading:
                  "${checkoutController.charge.value.toStringAsFixed(0)} ${LocalStorage.baseCurrencyCode}"),
          Divider(
            height: Dimensions.heightSize * 2.5,
            color: CustomColor.blackColor.withValues(alpha: 0.1),
            thickness: 1,
          ),
          Obx(
            () => TitleHeadingRow(
                leftHeading: Strings.totalPayableAmount,
                rightHeading:
                    "${checkoutController.calculateTotalPayable()} ${LocalStorage.baseCurrencyCode}"),
          ),
        ],
      ),
    );
  }
}
