import 'package:flutter/material.dart';
import 'package:get/get.dart';

import 'package:goldinvest/utils/responsive_layout.dart';
import 'package:goldinvest/widgets/common/app_bar/primary_app_bar.dart';
import 'package:goldinvest/widgets/common/others/custom_image_widget.dart';

import '../../backend/model/checkout/checkout_success_model.dart';
import '../../controller/checkout/checkout_controller.dart';
import '../../custom_assets/assets.gen.dart';
import '../../languages/strings.dart';
import '../../routes/routes.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../utils/size.dart';
import '../../widgets/common/buttons/primary_button.dart';
import '../../widgets/common/text_labels/title_heading3_widget.dart';
import '../../widgets/common/text_labels/title_heading4_widget.dart';

part 'gold_store_congratulation_mobile_screen.dart';

class GoldStoreCongratulationScreen extends StatelessWidget {
  const GoldStoreCongratulationScreen({
    super.key,
    required this.checkOutSuccessModel,
  });
  final CheckoutSuccessModel checkOutSuccessModel;

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
        mobileScaffold: GoldStoreCongratulationMobileScreen(
      checkOutSuccessModel: checkOutSuccessModel,
    ));
  }
}
