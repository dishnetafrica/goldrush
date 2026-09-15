import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/widgets/common/app_bar/primary_app_bar.dart';

import '../../backend/utils/custom_loading_api.dart';
import '../../controller/profile/chnage_password_controller.dart';
import '../../custom_assets/assets.gen.dart';
import '../../languages/strings.dart';
import '../../routes/routes.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../utils/responsive_layout.dart';
import '../../utils/size.dart';
import '../../widgets/common/app_bar/back_button.dart';
import '../../widgets/common/buttons/primary_button.dart';
import '../../widgets/common/inputs/primary_input_widget.dart';
part 'change_password_mobile_screen_layout.dart';

class ChangePasswordScreen extends StatelessWidget {
  const ChangePasswordScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(mobileScaffold: ChangePasswordMobileScreen());
  }
}
