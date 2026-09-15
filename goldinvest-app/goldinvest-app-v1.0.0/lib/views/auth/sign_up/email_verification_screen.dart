import 'package:dynamic_languages/dynamic_languages.dart';
import 'package:flutter/material.dart';

import 'package:get/get.dart';
import 'package:goldinvest/controller/auth/sign_up/email_verification_controller.dart';
import 'package:pin_code_fields/pin_code_fields.dart';

import '../../../backend/utils/custom_loading_api.dart';
import '../../../languages/strings.dart';
import '../../../routes/routes.dart';
import '../../../utils/custom_color.dart';
import '../../../utils/custom_style.dart';
import '../../../utils/dimensions.dart';
import '../../../utils/responsive_layout.dart';
import '../../../utils/size.dart';
import '../../../widgets/common/app_bar/back_button.dart';
import '../../../widgets/common/buttons/primary_button.dart';
import '../../../widgets/common/text_labels/title_heading4_widget.dart';
import '../../../widgets/common/text_labels/title_sub_title_widget.dart';
part 'email_verification_mobile_screen_layout.dart';

class EmailVerificationScreen extends StatelessWidget {
  const EmailVerificationScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const ResponsiveLayout(
      mobileScaffold: EmailVerificationMobileScreenLayout(),
    );
  }
}
