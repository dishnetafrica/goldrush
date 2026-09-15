import 'package:flutter/material.dart';
import 'package:goldinvest/backend/local_storage/local_storage.dart';
import 'package:goldinvest/utils/responsive_layout.dart';
import 'package:get/get.dart';
import 'package:goldinvest/routes/routes.dart';
import 'package:goldinvest/widgets/common/app_bar/primary_app_bar.dart';

import '../../controller/invest/invest_controller.dart';
import '../../custom_assets/assets.gen.dart';
import '../../languages/strings.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../utils/size.dart';
import '../../widgets/common/buttons/primary_button.dart';
import '../../widgets/common/others/custom_image_widget.dart';
import '../../widgets/common/text_labels/title_heading3_widget.dart';
import '../../widgets/common/text_labels/title_heading4_widget.dart';
import '../investment/investment_screen.dart';
part 'gold_invest_congratulation_mobile_screen.dart';

class GoldInvestCongratulationScreen extends StatelessWidget {
  const GoldInvestCongratulationScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
        mobileScaffold: GoldInvestCongratulationMobileScreen());
  }
}
