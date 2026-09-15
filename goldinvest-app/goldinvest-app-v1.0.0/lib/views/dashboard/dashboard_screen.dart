import 'dart:io';

import 'package:dotted_border/dotted_border.dart';
import 'package:dynamic_languages/dynamic_languages.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/controller/profile/profile_controller.dart';
import 'package:goldinvest/widgets/common/text_labels/title_heading5_widget.dart';
import 'package:intl/intl.dart';
import '../../backend/utils/custom_loading_api.dart';
import '../../controller/logs/logs_controller.dart';
import '../../custom_assets/assets.gen.dart';
import '../../languages/strings.dart';
import '../../routes/routes.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../utils/responsive_layout.dart';
import '../../utils/size.dart';
import '../../widgets/add_out_send_money/custom_tray_widget.dart';
import '../../widgets/common/others/custom_image_widget.dart';
import '../../widgets/common/text_labels/title_heading3_widget.dart';
import '../../widgets/dashboard_top_design_widget/dashboard_top_clip_path.dart';

import '../../widgets/time_widget/time_widget.dart';
import '../../widgets/wallet_card/wallet_card.dart';
import '../drawer/drawer_screen.dart';

part 'dashboard_mobile_screen_layput.dart';

class DashboardScreen extends StatelessWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(mobileScaffold: DashboardMobileScreenLayout());
  }
}
