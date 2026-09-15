// ignore_for_file: unnecessary_to_list_in_spreads

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../../../../backend/utils/custom_loading_api.dart';
import '../../../../backend/utils/custom_snackbar.dart';
import '../../controller/add_money_controller/add_money_controller.dart';
import '../../languages/strings.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../utils/size.dart';
import '../../widgets/common/app_bar/primary_app_bar.dart';
import '../../widgets/common/buttons/primary_button.dart';
import '../../widgets/common/text_labels/title_heading4_widget.dart';

class TatumPaymentScreen extends StatelessWidget {
  TatumPaymentScreen({super.key});
  final controller = Get.put(AddMoneyController());
  final formKey = GlobalKey<FormState>();
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: const PrimaryAppBar(Strings.cryptoPaymentAddress),
      backgroundColor: CustomColor.primaryLightScaffoldBackgroundColor,
      body: Obx(
        () => controller.isTatumProcessLoading
            ? const CustomLoadingAPI()
            : _bodyWidget(context),
      ),
    );
  }

  Obx _bodyWidget(BuildContext context) {
    return Obx(
      () => ListView(
        shrinkWrap: true,
        children: [
          Padding(
            padding: EdgeInsets.symmetric(
              horizontal: Dimensions.marginSizeHorizontal * 0.7,
              vertical: Dimensions.marginSizeVertical * 0.7,
            ),
            child: Form(
              key: formKey,
              child: Column(
                children: [
                  _qrCodeWidget(),
                  ...controller.inputFields.map((element) {
                    return element;
                  }).toList(),
                  _copyAddressWidget(context),
                ],
              ),
            ),
          ),
          _buttonWidget(context),
        ],
      ),
    );
  }

  Obx _buttonWidget(BuildContext context) {
    return Obx(
      () => controller.isTatumConfirmLoading
          ? const CustomLoadingAPI()
          : Container(
              margin: EdgeInsets.symmetric(
                horizontal: Dimensions.marginSizeHorizontal * 0.8,
              ),
              child: PrimaryButton(
                title: Strings.submit,
                buttonTextColor: CustomColor.whiteColor,
                onPressed: () {
                  // if (formKey.currentState!.validate()) {
                  controller.tatumConfirmProcess(context);
                  // }
                },
              ),
            ),
    );
  }

  Column _copyAddressWidget(BuildContext context) {
    return Column(
      crossAxisAlignment: crossStart,
      children: [
        const TitleHeading4Widget(
          text: Strings.payWithThisAddress,
          maxLines: 1,
          fontWeight: FontWeight.w500,
          color: CustomColor.primaryLightTextColor,
        ),
        verticalSpace(Dimensions.heightSize * 0.5),
        Container(
          decoration: BoxDecoration(
            border: Border.all(
              color: Get.isDarkMode
                  ? CustomColor.whiteColor
                  : CustomColor.primaryDarkColor.withValues(alpha: 0.4),
              width: 0.5,
            ),
            borderRadius: BorderRadius.circular(Dimensions.radius * 0.5),
          ),
          child: Row(
            children: [
              horizontalSpace(Dimensions.widthSize),
              Expanded(
                child: TitleHeading4Widget(
                  text: controller.qrAddress.value,
                  maxLines: 1,
                ),
              ),
              InkWell(
                onTap: () {
                  Clipboard.setData(
                          ClipboardData(text: controller.qrAddress.value))
                      .then((_) {
                    CustomSnackBar.success(Strings.addressCopyTo);
                  });
                },
                child: Container(
                  padding: EdgeInsets.all(Dimensions.paddingSize * 0.5),
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.only(
                      topRight: Radius.circular(Dimensions.radius * 0.5),
                      bottomRight: Radius.circular(Dimensions.radius * 0.5),
                    ),
                    color: CustomColor.primaryLightColor,
                  ),
                  child: const Icon(
                    Icons.copy,
                    color: CustomColor.whiteColor,
                  ),
                ),
              )
            ],
          ),
        ),
        verticalSpace(Dimensions.heightSize),
      ],
    );
  }

  Container _qrCodeWidget() {
    return Container(
      alignment: Alignment.center,
      margin: EdgeInsets.symmetric(
        vertical: Dimensions.marginSizeVertical * 0.8,
        horizontal: Dimensions.marginSizeHorizontal * 1.5,
      ),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(Dimensions.radius),
        color: CustomColor.whiteColor,
      ),
      padding: EdgeInsets.all(Dimensions.paddingSize * 0.8),
      child: QrImageView(
        data: controller.qrAddress.value,
        version: QrVersions.auto,
        size: 200.0,
      ),
    );
  }
}
