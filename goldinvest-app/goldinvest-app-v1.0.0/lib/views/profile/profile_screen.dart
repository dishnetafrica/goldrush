// ignore_for_file: library_prefixes

import 'dart:io';

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/controller/profile/profile_controller.dart';
import 'package:goldinvest/widgets/common/app_bar/primary_app_bar.dart';

import 'package:goldinvest/backend/model/profile/states_info_model.dart'
    as statesModel;
import 'package:goldinvest/widgets/common/text_labels/title_heading2_widget.dart';
import 'package:goldinvest/widgets/common/text_labels/title_heading3_widget.dart';
import '../../backend/model/profile/cities_info_model.dart' as citiesModel;
import '../../backend/model/profile/profile_info_model.dart';
import '../../backend/utils/custom_loading_api.dart';
import '../../controller/delivery_address/delivery_address_controller.dart';
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
import '../../widgets/common/others/custom_image_widget.dart';
import '../../widgets/common/text_labels/title_heading4_widget.dart';
import '../../widgets/drop_down/custom_dropdown_menu.dart';
import '../../widgets/image_picker/image_picker_widget.dart';
part 'profile_mobile_screen.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(mobileScaffold: ProfileMobileScreenLayout());
  }
}
