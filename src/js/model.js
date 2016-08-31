var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['appModel.js']}
    ]
};
Component.entryPoint = function(NS){
    var Y = Brick.YUI,
        SYS = Brick.mod.sys;

    NS.Team = Y.Base.create('team', SYS.AppModel, [], {
        structureName: 'Team'
    }, {
        ATTRS: {
            extends: {value: {}}
        }
    });

    NS.TeamList = Y.Base.create('teamList', SYS.AppModelList, [], {
        appItem: NS.Team,
    });

    NS.Member = Y.Base.create('member', SYS.AppModel, [], {
        structureName: 'Member',
        toReplace: function(){
            var user = this.get('user');
            return {
                id: this.get('id'),
                userid: this.get('userid'),
                userViewName: user.get('viewName'),
                userViewURL: user.get('viewURL'),
                userAvatarSrc24: user.get('avatarSrc24'),
                userAvatarSrc45: user.get('avatarSrc45'),
                userAvatarSrc90: user.get('avatarSrc90'),
                userAvatarSrc18: user.get('avatarSrc180'),
            };
        }
    }, {
        ATTRS: {
            user: {
                readOnly: true,
                getter: function(){
                    var userid = this.get('userid'),
                        userList = this.appInstance.getApp('uprofile').get('userList');

                    return userList.getById(userid);
                }
            }
        }
    });

    NS.MemberList = Y.Base.create('memberList', SYS.AppModelList, [], {
        appItem: NS.Member,
    });

    NS.Config = Y.Base.create('config', SYS.AppModel, [], {
        structureName: 'Config'
    });
};
