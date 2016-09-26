var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['appModel.js']}
    ]
};
Component.entryPoint = function(NS){
    var Y = Brick.YUI,
        SYS = Brick.mod.sys;

    NS.ATTRIBUTE = {
        user: {
            readOnly: true,
            getter: function(){
                var userid = this.get('userid'),
                    userList = this.appInstance.getApp('uprofile').get('userList');

                return userList.getById(userid);
            }
        }
    };

    NS.TeamUserRole = Y.Base.create('teamUserRole', SYS.AppModel, [], {
        structureName: 'TeamUserRole',
        isJoined: function(){
            return this.get('status') === 'joined';
        },
        isWaiting: function(){
            return this.get('status') === 'waiting';
        },
        isAdmin: function(){
            return NS.roles.isAdmin ||
                (this.get('role') === 'admin' && this.isJoined());
        },
    });


    NS.Team = Y.Base.create('team', SYS.AppModel, [], {
        structureName: 'Team',
    }, {
        ATTRS: {
            extends: {value: {}}
        }
    });

    NS.TeamList = Y.Base.create('teamList', SYS.AppModelList, [], {
        appItem: NS.Team,
    });

    NS.TeamListFilter = Y.Base.create('teamListFilter', SYS.AppResponse, [], {
        structureName: 'TeamListFilter'
    });

    NS.Member = Y.Base.create('member', SYS.AppModel, [], {
        structureName: 'Member',
        initializer: function(d){
            d = d || {};
            this.set('extends', d.extends || {});
        },
        toReplace: function(){
            var user = this.get('user'),
                ret = {
                    id: this.get('id'),
                    userid: this.get('userid'),
                    userViewName: user.get('viewName'),
                    userViewURL: user.get('viewURL'),
                    userAvatarSrc24: user.get('avatarSrc24'),
                    userAvatarSrc45: user.get('avatarSrc45'),
                    userAvatarSrc90: user.get('avatarSrc90'),
                    userAvatarSrc18: user.get('avatarSrc180'),
                },
                exts = this.get('extends');

            return ret;
        },
        myIsAdmin: function(){
            return this.get('myStatus') === 'joined' && this.get('myRole') === 'admin';
        },
    }, {
        ATTRS: {
            user: NS.ATTRIBUTE.user,
            extends: {}
        }
    });

    NS.MemberList = Y.Base.create('memberList', SYS.AppModelList, [], {
        appItem: NS.Member,
    });

    NS.MemberListFilter = Y.Base.create('MemberListFilter', SYS.AppResponse, [], {
        structureName: 'MemberListFilter'
    });

    NS.Config = Y.Base.create('config', SYS.AppModel, [], {
        structureName: 'Config'
    });
};
