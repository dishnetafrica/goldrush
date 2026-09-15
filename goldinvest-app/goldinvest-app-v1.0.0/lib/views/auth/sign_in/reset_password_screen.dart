import 'package:flutter/material.dart';
import 'package:goldinvest/backend/utils/custom_loading_api.dart';
import 'package:goldinvest/utils/responsive_layout.dart';
import 'package:get/get.dart';

import '../../../controller/auth/sign_in/reset_password_controller.dart';
import '../../../custom_assets/assets.gen.dart';
import '../../../languages/strings.dart';
import '../../../utils/custom_color.dart';
import '../../../utils/dimensions.dart';
import '../../../utils/size.dart';
import '../../../widgets/common/buttons/primary_button.dart';
import '../../../widgets/common/inputs/primary_input_widget.dart';
import '../../../widgets/common/text_labels/title_sub_title_widget.dart';

part 'reset_password_mobile_screen_layout.dart';

class ResetPasswordScreen extends StatelessWidget {
  const ResetPasswordScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: ResetPasswordMobileScreenLayout(),
    );
  }
}
