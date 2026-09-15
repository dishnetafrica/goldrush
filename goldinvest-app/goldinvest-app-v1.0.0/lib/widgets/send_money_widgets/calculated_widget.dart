import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/utils/dimensions.dart';
import 'package:goldinvest/widgets/text_span.dart/custom_text_span.dart';

import '../../backend/local_storage/local_storage.dart';
import '../../controller/send_money-controller/send_money_controller.dart';
import '../../languages/strings.dart';
import '../../utils/custom_color.dart';
import '../../utils/size.dart';

class CalculatedWidget extends StatelessWidget {
  final controller = Get.put(SendMoneyController());

  CalculatedWidget({super.key});

  @override
  Widget build(BuildContext context) {
    return Obx(
      () => Column(
        mainAxisAlignment: mainSpaceBet,
        children: [
          verticalSpace(Dimensions.paddingSize),
          CustomTextSpanWidget(
            firstText: Strings.exchangeRate,
            firstTextColor: CustomColor.blackColor.withValues(alpha: 0.4),
            secondText:
                " 1${LocalStorage.baseCurrencyCode} = 1 ${LocalStorage.baseCurrencyCode}",
          ),
          verticalSpace(Dimensions.paddingSize * 0.5),
          CustomTextSpanWidget(
            firstText: "Limit",
            firstTextColor: CustomColor.blackColor.withValues(alpha: 0.4),
            secondText:
                " ${controller.minLimit.value}${LocalStorage.baseCurrencyCode} = ${controller.maxLimit.value} ${LocalStorage.baseCurrencyCode}",
          ),
          verticalSpace(Dimensions.paddingSize * 0.5),
          CustomTextSpanWidget(
            firstText: Strings.charge,
            firstTextColor: CustomColor.blackColor.withValues(alpha: 0.4),
            secondText:
                " ${controller.fixedCharge.value} ${LocalStorage.baseCurrencyCode} + ${controller.percentCharge.value}%",
          ),
        ],
      ),
    );
  }
}
