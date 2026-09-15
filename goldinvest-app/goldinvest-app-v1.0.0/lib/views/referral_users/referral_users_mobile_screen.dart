part of 'referral_users_screen.dart';

class ReferralUsersMobileScreen extends StatelessWidget {
  const ReferralUsersMobileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: _bodyWidget(context),
    );
  }

  Padding _bodyWidget(BuildContext context) {
    return Padding(
      padding: EdgeInsets.all(Dimensions.paddingSize),
      child: Column(
        children: [
          const PrimaryAppBar(
            Strings.referralUsers,
            showBackButton: false,
          ),
          _allReferralsWidget(context)
        ],
      ),
    );
  }

  Expanded _allReferralsWidget(context) {
    return Expanded(
      child: ListView.builder(
        padding: EdgeInsets.zero,
        itemBuilder: (context, index) {
          return Card(
            elevation: 0.3,
            child: ListTile(
              leading: CustomImageWidget(
                path: Assets.logo.ellipse95.path,
                height: Dimensions.heightSize * 3.34,
                width: Dimensions.widthSize * 4,
              ),
              title: const TitleHeading4Widget(text: "jafenisakbor"),
              subtitle: TitleHeading4Widget(
                text: Strings.refId,
                color: CustomColor.blackColor.withValues(alpha: 0.4),
              ),
            ),
          );
        },
      ),
    );
  }
}
