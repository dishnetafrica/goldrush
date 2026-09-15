import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../controller/checkout/checkout_controller.dart';
import '../../controller/delivery_address/delivery_address_controller.dart';
import '../../custom_assets/assets.gen.dart';
import '../../languages/strings.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../utils/size.dart';
import '../common/others/custom_image_widget.dart';
import '../common/text_labels/title_heading2_widget.dart';
import '../common/text_labels/title_heading4_widget.dart';

class OrderProductInfoWidget extends StatelessWidget {
  OrderProductInfoWidget({super.key});
  final CheckoutController checkoutController = Get.put(CheckoutController());
  final DeliveryAddressController deliveryAddressController =
      Get.put(DeliveryAddressController());

  @override
  Widget build(BuildContext context) {
    final w = MediaQuery.of(context).size.width;
    return Padding(
      padding: EdgeInsets.only(top: Dimensions.paddingSize * 1.5),
      child: Column(
        crossAxisAlignment: crossStart,
        children: [
          Padding(
            padding: EdgeInsets.only(bottom: Dimensions.heightSize),
            child: const TitleHeading2Widget(
              text: Strings.productSummary,
              fontWeight: FontWeight.w800,
            ),
          ),
          Container(
              decoration: BoxDecoration(
                  color: CustomColor.blackColor.withValues(alpha: 0.05),
                  borderRadius: BorderRadius.circular(Dimensions.radius)),
              // width: w * 0.9,
              height: Dimensions.heightSize * 6.67,
              child: Row(
                children: [
                  Padding(
                    padding:
                        EdgeInsets.only(left: Dimensions.paddingSize * 0.5),
                    child: Container(
                        decoration: BoxDecoration(
                            borderRadius:
                                BorderRadius.circular(Dimensions.radius),
                            color: CustomColor.whiteColor),
                        child: Padding(
                          padding: EdgeInsets.all(Dimensions.paddingSize * 0.1),
                          child: Image.network(
                            checkoutController.goldImage.value,
                            height: Dimensions.heightSize * 4,
                            width: Dimensions.widthSize * 5,
                          ),
                        )),
                  ),
                  Padding(
                    padding: EdgeInsets.only(
                        left: Dimensions.paddingSize * 0.5,
                        top: Dimensions.paddingSize * 0.5),
                    child: Column(
                      crossAxisAlignment: crossStart,
                      children: [
                        SizedBox(
                          width: w * 0.4,
                          child: TitleHeading4Widget(
                            text: checkoutController.goldName.value,
                            color: CustomColor.liteBlack2,
                            fontWeight: FontWeight.w400,
                            textOverflow: TextOverflow.ellipsis,
                          ),
                        ),
                        RichText(
                          text: TextSpan(
                              text: checkoutController.goldPrice.value
                                  .toStringAsFixed(2),
                              style: TextStyle(
                                  color: CustomColor.primaryLightTextColor,
                                  fontWeight: FontWeight.w700,
                                  fontSize: Dimensions.headingTextSize4),
                              children: [
                                TextSpan(
                                    text: "/ 1 Bar",
                                    style: TextStyle(
                                        color: CustomColor.liteBlack2,
                                        fontWeight: FontWeight.w500,
                                        fontSize: Dimensions.headingTextSize6))
                              ]),
                        )
                      ],
                    ),
                  ),
                  // horizontalSpace(w * 0.00001),
                  Expanded(
                    child: Row(
                      children: [
                        Container(
                          height: Dimensions.widthSize * 1.6,
                          width: Dimensions.heightSize * 1.33,
                          decoration: BoxDecoration(
                              color: CustomColor.primaryLightColor,
                              borderRadius: BorderRadius.circular(
                                  Dimensions.radius * 0.5)),
                          child: IconButton(
                            onPressed: () {
                              checkoutController.decrement();
                            },
                            icon: CustomImageWidget(path: Assets.icon.minus2),
                            color: Colors.redAccent,
                            padding:
                                EdgeInsets.all(Dimensions.paddingSize * 0.15),
                            constraints: const BoxConstraints(),
                          ),
                        ),

                        horizontalSpace(w * 0.02),
                        // Quantity Display
                        Container(
                          alignment: Alignment.center,
                          height: Dimensions.heightSize * 2.2,
                          width: Dimensions.widthSize * 3.2,
                          decoration: BoxDecoration(
                            color: CustomColor.blackColor.withValues(alpha: 0.01),
                            border: Border.all(color: Colors.redAccent),
                            borderRadius:
                                BorderRadius.circular(Dimensions.radius * 0.3),
                          ),
                          child: Obx(() => Text(
                                checkoutController.count.value
                                    .toStringAsFixed(0),
                                style: TextStyle(
                                    fontSize: Dimensions.headingTextSize4,
                                    color: CustomColor.primaryLightColor,
                                    fontWeight: FontWeight.w400),
                              )),
                        ),
                        horizontalSpace(w * 0.02),

                        // Plus Button
                        Container(
                          height: Dimensions.widthSize * 1.6,
                          width: Dimensions.heightSize * 1.33,
                          decoration: BoxDecoration(
                              color: CustomColor.primaryLightColor,
                              borderRadius: BorderRadius.circular(
                                  Dimensions.radius * 0.5)),
                          child: IconButton(
                            onPressed: () {
                              checkoutController.increment();

                              // Handle minus button action
                            },
                            icon: CustomImageWidget(path: Assets.icon.plus2),
                            color: Colors.redAccent,
                            iconSize: Dimensions.iconSizeSmall,
                            padding:
                                EdgeInsets.all(Dimensions.paddingSize * 0.15),
                            constraints: const BoxConstraints(),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ))
        ],
      ),
    );
  }
}
