import 'package:dynamic_languages/dynamic_languages.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/controller/logs/logs_controller.dart';
import 'package:intl/intl.dart';

import '../../backend/local_storage/local_storage.dart';
import '../../backend/utils/custom_loading_api.dart';
import '../../languages/strings.dart';
import '../../routes/routes.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../utils/responsive_layout.dart';
import '../../widgets/common/app_bar/back_button.dart';
import '../../widgets/common/app_bar/primary_app_bar.dart';
import '../../widgets/time_widget/time_widget.dart';
import '../../widgets/wallet_card/wallet_card.dart';
part 'order_logs_mobile_screen.dart';

class OrderLogsScreen extends StatelessWidget {
  const OrderLogsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(mobileScaffold: OrderLogsMobileScreen());
  }
}
