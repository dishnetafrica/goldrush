import 'package:dynamic_languages/dynamic_languages.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/utils/responsive_layout.dart';
import 'package:intl/intl.dart';

import '../../backend/local_storage/local_storage.dart';
import '../../controller/logs/logs_controller.dart';
import '../../languages/strings.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../widgets/common/app_bar/primary_app_bar.dart';

import '../../widgets/tab/tab.dart';
import '../../widgets/time_widget/time_widget.dart';
import '../../widgets/wallet_card/wallet_card.dart';
part 'history_mobile_screen_layout.dart';

class HistoryScreen extends StatelessWidget {
  final int? selectTab;

  const HistoryScreen({super.key, this.selectTab});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
        mobileScaffold: HistoryMobileScreenLayout(
      selectedPage: selectTab,
    ));
  }
}
