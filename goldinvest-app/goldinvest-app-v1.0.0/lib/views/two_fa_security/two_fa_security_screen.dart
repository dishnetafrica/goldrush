import 'package:dynamic_languages/dynamic_languages.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:goldinvest/utils/responsive_layout.dart';

import 'package:get/get.dart';
import 'package:goldinvest/widgets/common/others/custom_image_widget.dart';
import 'package:goldinvest/widgets/common/text_labels/title_sub_title_widget.dart';

import '../../backend/utils/custom_loading_api.dart';
import '../../controller/two_fa_verification_controller/two_fa_verification_controller.dart';
import '../../custom_assets/assets.gen.dart';
import '../../languages/strings.dart';
import '../../routes/routes.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../widgets/common/app_bar/back_button.dart';
import '../../widgets/common/app_bar/primary_app_bar.dart';
import '../../widgets/common/buttons/primary_button.dart';
import '../../widgets/common/inputs/primary_input_widget.dart';
part 'two_fa_security_mobile_screen_layout.dart';

class TwoFaScreen extends StatelessWidget {
  const TwoFaScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(mobileScaffold: TwoFaMobileScreenLayout());
  }
}
