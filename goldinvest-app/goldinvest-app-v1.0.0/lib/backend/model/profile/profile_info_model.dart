import 'dart:convert';

import '../../../widgets/drop_down/custom_dropdown_menu.dart';

ProfileInfoModel profileInfoModelFromJson(String str) =>
    ProfileInfoModel.fromJson(json.decode(str));

String profileInfoModelToJson(ProfileInfoModel data) =>
    json.encode(data.toJson());

class ProfileInfoModel {
  Message message;
  Data data;
  String type;

  ProfileInfoModel({
    required this.message,
    required this.data,
    required this.type,
  });

  factory ProfileInfoModel.fromJson(Map<String, dynamic> json) =>
      ProfileInfoModel(
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
  Instructions instructions;
  UserInfo userInfo;
  ImagePaths imagePaths;
  List<Country> countries;

  Data({
    required this.instructions,
    required this.userInfo,
    required this.imagePaths,
    required this.countries,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        instructions: Instructions.fromJson(json["instructions"]),
        userInfo: UserInfo.fromJson(json["user_info"]),
        imagePaths: ImagePaths.fromJson(json["image_paths"]),
        countries: List<Country>.from(
            json["countries"].map((x) => Country.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "instructions": instructions.toJson(),
        "user_info": userInfo.toJson(),
        "image_paths": imagePaths.toJson(),
        "countries": List<dynamic>.from(countries.map((x) => x.toJson())),
      };
}

class Country implements DropdownMenuModel {
  int id;
  String name;
  String mobileCode;
  String currencyName;
  String currencyCode;
  String currencySymbol;

  Country({
    required this.id,
    required this.name,
    required this.mobileCode,
    required this.currencyName,
    required this.currencyCode,
    required this.currencySymbol,
  });

  factory Country.fromJson(Map<String, dynamic> json) => Country(
        id: json["id"],
        name: json["name"],
        mobileCode: json["mobile_code"],
        currencyName: json["currency_name"],
        currencyCode: json["currency_code"],
        currencySymbol: json["currency_symbol"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "name": name,
        "mobile_code": mobileCode,
        "currency_name": currencyName,
        "currency_code": currencyCode,
        "currency_symbol": currencySymbol,
      };

  @override
  String get title => name;
}

class ImagePaths {
  String baseUrl;
  String pathLocation;
  String defaultImage;

  ImagePaths({
    required this.baseUrl,
    required this.pathLocation,
    required this.defaultImage,
  });

  factory ImagePaths.fromJson(Map<String, dynamic> json) => ImagePaths(
        baseUrl: json["base_url"],
        pathLocation: json["path_location"],
        defaultImage: json["default_image"],
      );

  Map<String, dynamic> toJson() => {
        "base_url": baseUrl,
        "path_location": pathLocation,
        "default_image": defaultImage,
      };
}

class Instructions {
  String kycVerified;

  Instructions({
    required this.kycVerified,
  });

  factory Instructions.fromJson(Map<String, dynamic> json) => Instructions(
        kycVerified: json["kyc_verified"],
      );

  Map<String, dynamic> toJson() => {
        "kyc_verified": kycVerified,
      };
}

class UserInfo {
  int id;
  String firstname;
  String lastname;
  String username;
  String email;
  String? mobileCode;
  String? mobile;
  String? image;
  int kycVerified;
  String country;
  String city;
  String state;
  String postalCode;
  String address;
  // Kyc kyc;
  String level;

  UserInfo({
    required this.id,
    required this.firstname,
    required this.lastname,
    required this.username,
    required this.email,
    this.mobileCode,
    this.mobile,
    this.image,
    required this.kycVerified,
    required this.country,
    required this.city,
    required this.state,
    required this.postalCode,
    required this.address,
    // required this.kyc,
    required this.level,
  });

  factory UserInfo.fromJson(Map<String, dynamic> json) => UserInfo(
        id: json["id"],
        firstname: json["firstname"],
        lastname: json["lastname"],
        username: json["username"],
        email: json["email"],
        mobileCode: json["mobile_code"] ?? "",
        mobile: json["mobile"] ?? "",
        image: json["image"] ?? "",
        kycVerified: json["kyc_verified"],
        country: json["country"],
        city: json["city"],
        state: json["state"],
        postalCode: json["postal_code"],
        address: json["address"],
        // kyc: Kyc.fromJson(json["kyc"]),
        level: json["level"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "firstname": firstname,
        "lastname": lastname,
        "username": username,
        "email": email,
        "mobile_code": mobileCode,
        "mobile": mobile,
        "image": image,
        "kyc_verified": kycVerified,
        "country": country,
        "city": city,
        "state": state,
        "postal_code": postalCode,
        "address": address,
        // "kyc": kyc.toJson(),
        "level": level,
      };
}

// class Kyc {
//   List<Datum>? data; // Nullable list
//   String? rejectReason; // Nullable String

//   Kyc({
//     this.data,
//     this.rejectReason,
//   });

//   factory Kyc.fromJson(Map<String, dynamic>? json) {
//     if (json == null) {
//       return Kyc(); // Return an empty Kyc object if json is null
//     }
//     return Kyc(
//       data: json["data"] != null
//           ? List<Datum>.from(json["data"].map((x) => Datum.fromJson(x)))
//           : null, // Handle null data
//       rejectReason:
//           json["reject_reason"] ?? '', // Provide default value if null
//     );
//   }

//   Map<String, dynamic> toJson() => {
//         "data": data != null
//             ? List<dynamic>.from(data!.map((x) => x.toJson()))
//             : null,
//         "reject_reason": rejectReason ?? '', // Provide default value
//       };
// }

class Datum {
  String? type; // Nullable
  String? label; // Nullable
  String? name; // Nullable
  bool? required; // Nullable
  Validation? validation; // Nullable
  String? value; // Nullable

  Datum({
    this.type,
    this.label,
    this.name,
    this.required,
    this.validation,
    this.value,
  });

  factory Datum.fromJson(Map<String, dynamic>? json) {
    if (json == null) {
      return Datum(); // Return an empty Datum object if json is null
    }
    return Datum(
      type: json["type"] ?? '', // Provide default value
      label: json["label"] ?? '',
      name: json["name"] ?? '',
      required: json["required"] ?? false, // Default value for bool
      validation: json["validation"] != null
          ? Validation.fromJson(json["validation"])
          : null, // Handle null validation
      value: json["value"] ?? '', // Provide default value
    );
  }

  Map<String, dynamic> toJson() => {
        "type": type ?? '', // Provide default value
        "label": label ?? '',
        "name": name ?? '',
        "required": required ?? false, // Provide default value
        "validation": validation?.toJson(), // Handle nullable validation
        "value": value ?? '', // Provide default value
      };
}

class Validation {
  dynamic max;
  List<String> mimes;
  int min;
  List<String> options;
  bool required;

  Validation({
    required this.max,
    required this.mimes,
    required this.min,
    required this.options,
    required this.required,
  });

  factory Validation.fromJson(Map<String, dynamic> json) => Validation(
        max: json["max"],
        mimes: List<String>.from(json["mimes"].map((x) => x)),
        min: json["min"],
        options: List<String>.from(json["options"].map((x) => x)),
        required: json["required"],
      );

  Map<String, dynamic> toJson() => {
        "max": max,
        "mimes": List<dynamic>.from(mimes.map((x) => x)),
        "min": min,
        "options": List<dynamic>.from(options.map((x) => x)),
        "required": required,
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
