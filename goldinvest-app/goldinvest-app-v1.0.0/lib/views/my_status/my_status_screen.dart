import 'dart:io';

import 'package:dotted_border/dotted_border.dart';
import 'package:dynamic_languages/dynamic_languages.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:goldinvest/controller/profile/profile_controller.dart';
import 'package:goldinvest/utils/responsive_layout.dart';
import 'package:goldinvest/widgets/common/text_labels/title_heading2_widget.dart';
import 'package:goldinvest/widgets/common/text_labels/title_heading3_widget.dart';
import 'package:goldinvest/widgets/common/text_labels/title_heading4_widget.dart';
import 'package:goldinvest/widgets/common/text_labels/title_heading5_widget.dart';

import '../../backend/local_storage/local_storage.dart';
import '../../backend/utils/custom_loading_api.dart';
import '../../controller/status/status_contorller.dart';
import '../../custom_assets/assets.gen.dart';
import '../../languages/strings.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';

import '../../utils/size.dart';
import '../../widgets/common/app_bar/primary_app_bar.dart';
import '../../widgets/common/inputs/primary_input_widget.dart';
import '../../widgets/common/others/custom_image_widget.dart';

part 'my_status_mobile_screen_layout.dart';

class StatusScreen extends StatelessWidget {
  const StatusScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(mobileScaffold: StatusMobileScreenLayout());
  }
}
