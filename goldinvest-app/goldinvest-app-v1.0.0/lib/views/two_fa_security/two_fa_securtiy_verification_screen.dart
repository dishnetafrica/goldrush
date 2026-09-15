import 'package:dynamic_languages/dynamic_languages.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/utils/responsive_layout.dart';
import 'package:pin_code_fields/pin_code_fields.dart';

import '../../backend/utils/custom_loading_api.dart';
import '../../controller/two_fa_verification_controller/two_fa_seceurity_verify_controller.dart';
import '../../languages/strings.dart';
import '../../utils/custom_color.dart';
import '../../utils/custom_style.dart';
import '../../utils/dimensions.dart';
import '../../utils/size.dart';
import '../../widgets/common/app_bar/back_button.dart';
import '../../widgets/common/buttons/primary_button.dart';
import '../../widgets/common/text_labels/title_heading4_widget.dart';
import '../../widgets/common/text_labels/title_sub_title_widget.dart';

part 'two_fa_security_verification_mobile_screen.dart';

class TwoFaSecurityOtpVerificationScreen extends StatelessWidget {
  const TwoFaSecurityOtpVerificationScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: TwoFaSecurityVerificationScreen(),
    );
  }
}
