import 'package:flutter/material.dart';
import 'package:goldinvest/utils/responsive_layout.dart';
import 'package:get/get.dart';

import '../../backend/local_storage/local_storage.dart';
import '../../controller/add_money_controller/add_money_controller.dart';
import '../../controller/navigation/navigation_controller.dart';
import '../../custom_assets/assets.gen.dart';
import '../../languages/strings.dart';
import '../../routes/routes.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../utils/size.dart';
import '../../widgets/common/app_bar/primary_app_bar.dart';
import '../../widgets/common/buttons/primary_button.dart';
import '../../widgets/common/others/custom_image_widget.dart';
import '../../widgets/common/text_labels/title_heading3_widget.dart';
import '../../widgets/common/text_labels/title_heading4_widget.dart';

part 'add_money_congratulation_mobile_screen.dart';

class AddMoneyCongratulationScreen extends StatelessWidget {
  const AddMoneyCongratulationScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
        mobileScaffold: AddMoneyCongratulationMobileScreen());
  }
}
