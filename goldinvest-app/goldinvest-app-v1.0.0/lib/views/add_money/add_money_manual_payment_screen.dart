import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../controller/add_money_controller/add_money_controller.dart';
import '../../languages/strings.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../widgets/common/app_bar/primary_app_bar.dart';
import '../../widgets/common/buttons/primary_button.dart';

class AddMoneyManualPaymentScreen extends StatelessWidget {
  AddMoneyManualPaymentScreen({super.key});

  final controller = Get.put(AddMoneyController());
  final formKey = GlobalKey<FormState>();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: const PrimaryAppBar(Strings.evidenceNote),
      body: _bodyWidget(context),
    );
  }

  Padding _bodyWidget(BuildContext context) {
    return Padding(
      padding: EdgeInsets.symmetric(
        horizontal: Dimensions.paddingSize * 0.7,
        vertical: Dimensions.paddingSize * 0.7,
      ),
      child: Form(
        key: formKey,
        child: ListView(
          physics: const BouncingScrollPhysics(),
          children: [
            ...controller.inputFields.map((element) {
              return element;
            }),
            _buttonWidget(context)
          ],
        ),
      ),
    );
  }

  Container _buttonWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.symmetric(vertical: Dimensions.marginSizeVertical),
      child: Obx(
        () => PrimaryButton(
          isLoading: controller.isConfirmLoading,
          title: Strings.submit,
          buttonTextColor: CustomColor.whiteColor,
          onPressed: () {
            controller.confirmProcess();
          },
        ),
      ),
    );
  }
}
