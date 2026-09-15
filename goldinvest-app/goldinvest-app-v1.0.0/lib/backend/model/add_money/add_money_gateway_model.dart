import 'dart:convert';

AddMoneyGatewayModel addMoneyGatewayModelFromJson(String str) =>
    AddMoneyGatewayModel.fromJson(json.decode(str));

String addMoneyGatewayModelToJson(AddMoneyGatewayModel data) =>
    json.encode(data.toJson());

class AddMoneyGatewayModel {
  Message message;
  Data data;
  String type;

  AddMoneyGatewayModel({
    required this.message,
    required this.data,
    required this.type,
  });

  factory AddMoneyGatewayModel.fromJson(Map<String, dynamic> json) =>
      AddMoneyGatewayModel(
        message: Message.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
        type: json["type"],
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
        "type": type,
      };
}

class Data {
  final dynamic redirectUrl;
  List<RedirectLink> redirectLinks;
  String actionType;
  List<dynamic> addressInfo;

  Data({
    required this.redirectUrl,
    required this.redirectLinks,
    required this.actionType,
    required this.addressInfo,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        redirectUrl: json["redirect_url"].toString(),
        redirectLinks: List<RedirectLink>.from(
            json["redirect_links"].map((x) => RedirectLink.fromJson(x))),
        actionType: json["action_type"],
        addressInfo: json["address_info"] is List
            ? List<dynamic>.from(json["address_info"])
            : [],
      );

  Map<String, dynamic> toJson() => {
        "redirect_url": redirectUrl,
        "redirect_links":
            List<dynamic>.from(redirectLinks.map((x) => x.toJson())),
        "action_type": actionType,
        "address_info": addressInfo,
      };
}

class RedirectLink {
  String href;
  String rel;
  String method;

  RedirectLink({
    required this.href,
    required this.rel,
    required this.method,
  });

  factory RedirectLink.fromJson(Map<String, dynamic> json) => RedirectLink(
        href: json["href"],
        rel: json["rel"],
        method: json["method"],
      );

  Map<String, dynamic> toJson() => {
        "href": href,
        "rel": rel,
        "method": method,
      };
}

class Message {
  List<String> success;

  Message({
    required this.success,
  });

  factory Message.fromJson(Map<String, dynamic> json) => Message(
        success: List<String>.from(json["success"].map((x) => x)),
      );

  Map<String, dynamic> toJson() => {
        "success": List<dynamic>.from(success.map((x) => x)),
      };
}
