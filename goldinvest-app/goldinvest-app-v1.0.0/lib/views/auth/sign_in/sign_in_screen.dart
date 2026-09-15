import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/backend/utils/custom_loading_api.dart';
import 'package:goldinvest/utils/custom_color.dart';
import 'package:goldinvest/utils/responsive_layout.dart';
import 'package:goldinvest/utils/size.dart';

import '../../../controller/auth/sign_in/sign_in_controller.dart';
import '../../../custom_assets/assets.gen.dart';
import '../../../languages/strings.dart';
import '../../../routes/routes.dart';
import '../../../utils/dimensions.dart';
import '../../../widgets/common/app_bar/back_button.dart';
import '../../../widgets/common/buttons/primary_button.dart';
import '../../../widgets/common/inputs/primary_input_widget.dart';
import '../../../widgets/common/text_labels/title_sub_title_widget.dart';
import '../../../widgets/sing_in_widgets/sign_in_bottom_sheet.dart';
import '../../../widgets/text_span.dart/custom_text_span.dart';

part 'sign_in_mobile_screen_layout.dart';

class SignInScreen extends StatelessWidget {
  const SignInScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(mobileScaffold: SignInMobileScreenLayout());
  }
}
