var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['editor.js']},
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI;

    var MemberViewerWidgetExt = function(){
    };
    MemberViewerWidgetExt.ATTRS = {
        teamApp: NS.ATTRIBUTE.teamApp,
        teamid: NS.ATTRIBUTE.teamid,
        memberid: NS.ATTRIBUTE.memberid,
        member: NS.ATTRIBUTE.member,
    };
    MemberViewerWidgetExt.prototype = {
        onInitAppWidget: function(err, appInstance){
            var teamApp = this.get('teamApp'),
                teamid = this.get('teamid'),
                memberid = this.get('memberid'),
                member;

            this.set('waiting', true);

            teamApp.member(teamid, memberid, function(err, result){
                member = err ? null : result.member;
                this._onLoadMember(result.member);
            }, this);
        },
        _onLoadMember: function(member){
            this.set('waiting', false);
            this.set('member', member);

            if (!member){
                // TODO: show member not found error
                return;
            }

            var tp = this.template,
                teamid = this.get('teamid');

            tp.setHTML(member.toReplace());

            this.onLoadMember(member);
        },
        onLoadMember: function(member){
        },
    };
    NS.MemberViewerWidgetExt = MemberViewerWidgetExt;
};
