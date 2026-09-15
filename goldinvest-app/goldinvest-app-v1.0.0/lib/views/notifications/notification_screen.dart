import 'package:dynamic_languages/dynamic_languages.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/utils/size.dart';

import 'package:intl/intl.dart';

import '../../backend/utils/custom_loading_api.dart';
import '../../controller/notification/notification_controller.dart';
import '../../languages/strings.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../utils/responsive_layout.dart';
import '../../widgets/common/app_bar/primary_app_bar.dart';
import '../../widgets/common/text_labels/title_heading4_widget.dart';
import '../../widgets/time_widget/time_widget.dart';
part 'notification_mobile_screen.dart';

class NotificationScreen extends StatelessWidget {
  const NotificationScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(mobileScaffold: NotificationMobileScreenLayout());
  }
}
