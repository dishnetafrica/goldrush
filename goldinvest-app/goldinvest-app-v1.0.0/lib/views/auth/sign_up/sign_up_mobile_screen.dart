import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/backend/utils/custom_loading_api.dart';

import '../../../backend/utils/custom_snackbar.dart';
import '../../../controller/auth/sign_up/registration_controller.dart';
import '../../../controller/basic_settings_controller/basic_settings_controller.dart';
import '../../../custom_assets/assets.gen.dart';
import '../../../languages/strings.dart';
import '../../../routes/routes.dart';
import '../../../utils/custom_color.dart';
import '../../../utils/dimensions.dart';
import '../../../utils/responsive_layout.dart';
import '../../../utils/size.dart';
import '../../../widgets/common/app_bar/back_button.dart';
import '../../../widgets/common/buttons/primary_button.dart';
import '../../../widgets/common/inputs/primary_input_widget.dart';
import '../../../widgets/common/text_labels/title_sub_title_widget.dart';
import '../../../widgets/sing_up_widgets/sign_up_widget.dart';
import '../../../widgets/text_span.dart/custom_text_span.dart';

part 'sign_up_mobile_screen_layout.dart';

class SignUpScreen extends StatelessWidget {
  const SignUpScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(mobileScaffold: SignUpMobileScreenLayout());
  }
}
