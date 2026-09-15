import 'package:cached_network_image/cached_network_image.dart';
import 'package:dynamic_languages/dynamic_languages.dart';
import 'package:flutter/material.dart';
import 'package:goldinvest/backend/utils/custom_loading_api.dart';
import 'package:goldinvest/languages/language_drop_down.dart';
import 'package:goldinvest/utils/responsive_layout.dart';
import 'package:get/get.dart';

import 'package:goldinvest/utils/size.dart';
import 'package:goldinvest/widgets/common/text_labels/title_heading1_widget.dart';

import '../../controller/basic_settings_controller/basic_settings_controller.dart';
import '../../controller/on_board_controller/onboard_controller.dart';
import '../../languages/strings.dart';
import '../../routes/routes.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../widgets/common/buttons/primary_button.dart';
part 'onboard_mobile_screen.dart';

class OnboardScreen extends StatelessWidget {
  const OnboardScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const ResponsiveLayout(mobileScaffold: OnboardMobileScreen());
  }
}
