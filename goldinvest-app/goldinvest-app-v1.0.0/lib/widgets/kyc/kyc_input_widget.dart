import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../backend/utils/custom_loading_api.dart';
import '../../controller/kyc/kyc_controller.dart';
import '../../utils/dimensions.dart';
import '../../utils/size.dart';

class KycInputWidget extends GetView<KycInformationController> {
  const KycInputWidget({super.key});

  @override
  Widget build(BuildContext context) {
    return Obx(() => controller.isLoading
        ? const CustomLoadingAPI()
        : Column(
            mainAxisSize: mainMin,
            children: [
              _inputsWidget(context),
            ],
          ));
  }

  Column _inputsWidget(BuildContext context) {
    return Column(
      mainAxisSize: mainMin,
      children: [
        ...controller.inputFields.map((element) {
          return element;
        }),
        _filePickerWidget(context),
      ],
    );
  }

  SizedBox _filePickerWidget(BuildContext context) {
    if (controller.status.value == 0 || controller.status.value == 3) {
      return SizedBox(
        height: MediaQuery.sizeOf(context).height * 0.36,
        child: GridView.builder(
          padding: EdgeInsets.symmetric(
            horizontal: Dimensions.paddingSize,
          ),
          physics: const BouncingScrollPhysics(),
          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 2,
            crossAxisSpacing: 10.0,
          ),
          itemCount: controller.inputFileFields.length,
          itemBuilder: (BuildContext context, int index) {
            return controller.inputFileFields[index];
          },
        ),
      );
    } else {
      return const SizedBox.shrink();
    }
  }
}
