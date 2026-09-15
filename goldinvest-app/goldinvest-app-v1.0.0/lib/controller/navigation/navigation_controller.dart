import 'package:flutter/widgets.dart';
import 'package:get/get.dart';
import 'package:goldinvest/views/dashboard/dashboard_screen.dart';
import 'package:goldinvest/views/history/history_screen.dart';
import 'package:goldinvest/views/notifications/notification_screen.dart';

import '../../views/my_status/my_status_screen.dart';

class NavigationController extends GetxController {
  var selectedIndex = 0.obs;

  List<Widget> bodyPages = [
    const DashboardScreen(),
    const NotificationScreen(),
    const StatusScreen(),
    const HistoryScreen(
      selectTab: 0,
    )
  ];

  void changePage(int index) {
    selectedIndex.value = index;
  }

  Widget getCurrentPage(int selectedIndex) {
    return bodyPages[selectedIndex];
  }
}
